<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerChild;
use App\Models\Role;
use App\Models\Wallet;
use App\Models\Student;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserCreationService
{
    /**
     * Tạo user từ enrollment
     * 
     * @param \App\Models\Enrollment $enrollment
     * @return array ['customer_user' => User|null, 'student_user' => User|null]
     */
    public function createUsersFromEnrollment($enrollment)
    {
        Log::info('[UserCreation] ================================');
        Log::info('[UserCreation] 🔵 createUsersFromEnrollment CALLED', [
            'enrollment_id' => $enrollment->id,
            'enrollment_code' => $enrollment->code,
            'enrollment_status' => $enrollment->status,
            'customer_id' => $enrollment->customer_id,
            'student_id' => $enrollment->student_id,
            'student_type' => $enrollment->student_type,
            'branch_id' => $enrollment->branch_id,
        ]);
        
        $result = [
            'customer_user' => null,
            'student_user' => null,
        ];

        try {
            $customer = $enrollment->customer;
            $student = $enrollment->student;
            $studentType = $enrollment->student_type;

            Log::info('[UserCreation] 📦 Loaded relationships', [
                'customer' => $customer ? "ID:{$customer->id} Name:{$customer->name}" : 'NULL',
                'student' => $student ? "ID:{$student->id} Type:" . get_class($student) : 'NULL',
                'student_type' => $studentType,
            ]);

            // Case 1: Khách hàng chính là học viên (Customer = Student)
            if ($studentType === 'App\\Models\\Customer' && $customer->id == $student->id) {
                Log::info('[UserCreation] 📍 CASE 1: Customer is Student');
                $result['customer_user'] = $this->createStudentUser($customer, $enrollment->branch_id);
                $result['student_user'] = $result['customer_user']; // Same user
            }
            // Case 2: Đăng ký cho con (Child = Student)
            else if ($studentType === 'App\\Models\\CustomerChild') {
                Log::info('[UserCreation] 📍 CASE 2: Child is Student');
                // Tạo user cho phụ huynh (nếu chưa có)
                $result['customer_user'] = $this->createParentUser($customer, $enrollment->branch_id);
                
                // Tạo user cho học viên (con)
                $result['student_user'] = $this->createStudentUserFromChild($student, $enrollment->branch_id, $customer);
            } else {
                Log::warning('[UserCreation] ⚠️ UNKNOWN CASE!', [
                    'student_type' => $studentType,
                    'customer_id' => $customer?->id,
                    'student_id' => $student?->id,
                ]);
            }

            Log::info('[UserCreation] ✅ Users created from enrollment', [
                'enrollment_id' => $enrollment->id,
                'customer_user_id' => $result['customer_user']?->id,
                'student_user_id' => $result['student_user']?->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[UserCreation] ❌ EXCEPTION in createUsersFromEnrollment', [
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('[UserCreation] ================================');
        return $result;
    }

    /**
     * Tạo user cho học viên (từ Customer)
     */
    protected function createStudentUser(Customer $customer, int $branchId): ?User
    {
        // Kiểm tra xem customer đã có user chưa
        if ($customer->user_id) {
            $existingUser = User::find($customer->user_id);
            if ($existingUser) {
                // Nếu đã có user, đảm bảo có role user (không gán student - dựa trên bảng students)
                $this->ensureRole($existingUser, 'user');
                
                // Đảm bảo có trong branch_user
                if (!$existingUser->branches()->where('branch_id', $branchId)->exists()) {
                    $existingUser->branches()->attach($branchId);
                }
                
                // Đảm bảo có record trong students table
                if (!Student::where('user_id', $existingUser->id)->exists()) {
                    Student::create([
                        'user_id' => $existingUser->id,
                        'student_code' => Student::generateStudentCode(),
                        'branch_id' => $branchId,
                        'enrollment_date' => now(),
                        'is_active' => true,
                    ]);
                }
                
                return $existingUser;
            }
        }

        // Tạo username từ phone hoặc email
        $username = $this->generateUsername($customer->phone, $customer->email);
        
        // Tạo mật khẩu mặc định
        $defaultPassword = $this->generateDefaultPassword($customer->phone);

        try {
            $user = User::create([
                'name' => $customer->name,
                'email' => $customer->email ?: "{$username}@school.local",
                'username' => $username,
                'password' => Hash::make($defaultPassword),
                'phone' => $customer->phone,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // If duplicate email/username, try to find existing user
            if ($e->getCode() == 23000) { // Integrity constraint violation
                \Log::warning('Duplicate user email/username, searching for existing user', [
                    'email' => $customer->email,
                    'username' => $username,
                    'error' => $e->getMessage(),
                ]);
                
                $user = User::where('email', $customer->email)
                    ->orWhere('username', $username)
                    ->first();
                    
                if (!$user) {
                    throw $e; // Re-throw if we can't find the user
                }
                
                \Log::info('Found existing user, using that instead', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
            } else {
                throw $e; // Re-throw if not a duplicate error
            }
        }

        // Gán role user (không gán student role - permissions dựa trên bảng students)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole && !$user->roles()->where('role_id', $userRole->id)->exists()) {
            $user->roles()->attach($userRole->id);
        }

        // Tạo wallet cho user
        if (!Wallet::where('owner_type', User::class)->where('owner_id', $user->id)->exists()) {
            Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'code' => 'WAL' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
        }

        // Thêm user vào branch_user
        if (!$user->branches()->where('branch_id', $branchId)->exists()) {
            $user->branches()->attach($branchId);
        }

        // Tạo record trong bảng students (check if not exists)
        if (!Student::where('user_id', $user->id)->exists()) {
            Student::create([
                'user_id' => $user->id,
                'student_code' => Student::generateStudentCode(),
                'branch_id' => $branchId,
                'enrollment_date' => now(),
                'is_active' => true,
            ]);
        }

        // Update customer với user_id
        $customer->update(['user_id' => $user->id]);

        Log::info("Created student user for customer", [
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'username' => $username,
            'default_password' => $defaultPassword, // Log để thông báo cho admin
        ]);

        return $user;
    }

    /**
     * Tạo user cho phụ huynh
     */
    protected function createParentUser(Customer $customer, int $branchId): ?User
    {
        Log::info('[UserCreation] 🔵 createParentUser called', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_user_id' => $customer->user_id,
            'branch_id' => $branchId,
        ]);
        
        // Kiểm tra xem customer đã có user chưa
        if ($customer->user_id) {
            Log::info('[UserCreation] ✅ Customer already has user_id', ['user_id' => $customer->user_id]);
            $existingUser = User::find($customer->user_id);
            if ($existingUser) {
                // Nếu đã có user, đảm bảo có role user (không gán parent - dựa trên bảng parents)
                $this->ensureRole($existingUser, 'user');
                
                // Đảm bảo có trong branch_user
                if (!$existingUser->branches()->where('branch_id', $branchId)->exists()) {
                    $existingUser->branches()->attach($branchId);
                }
                
                // Đảm bảo có record trong parents table
                if (!ParentModel::where('user_id', $existingUser->id)->exists()) {
                    Log::info('[UserCreation] Creating parent record for existing user', ['user_id' => $existingUser->id]);
                    ParentModel::create([
                        'user_id' => $existingUser->id,
                        'branch_id' => $branchId,
                        'is_active' => true,
                    ]);
                }
                
                return $existingUser;
            }
        }

        // Tạo username từ phone hoặc email
        $username = $this->generateUsername($customer->phone, $customer->email);
        $email = $customer->email ?: "{$username}@school.local";
        
        // Check if user already exists with this email
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::info('[UserCreation] ✅ Found existing user with same email', [
                'user_id' => $existingUser->id,
                'email' => $email,
            ]);

            // Ensure has user role (không gán parent - dựa trên bảng parents)
            $this->ensureRole($existingUser, 'user');
            
            // Ensure in branch
            if (!$existingUser->branches()->where('branch_id', $branchId)->exists()) {
                $existingUser->branches()->attach($branchId);
            }
            
            // Ensure parent record exists
            if (!ParentModel::where('user_id', $existingUser->id)->exists()) {
                ParentModel::create([
                    'user_id' => $existingUser->id,
                    'branch_id' => $branchId,
                    'is_active' => true,
                ]);
            }
            
            // Update customer
            $customer->update(['user_id' => $existingUser->id]);
            
            return $existingUser;
        }
        
        // Tạo mật khẩu mặc định
        $defaultPassword = $this->generateDefaultPassword($customer->phone);

        Log::info('[UserCreation] 📝 Creating new parent user', [
            'username' => $username,
            'email' => $email,
        ]);

        try {
            $user = User::create([
                'name' => $customer->name,
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($defaultPassword),
                'phone' => $customer->phone,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
            
            Log::info('[UserCreation] ✅ Parent user created', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('[UserCreation] ❌ Failed to create parent user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Gán role user (không gán parent role - permissions dựa trên bảng parents)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole && !$user->roles()->where('role_id', $userRole->id)->exists()) {
            $user->roles()->attach($userRole->id);
        }

        // Tạo wallet cho user
        if (!Wallet::where('owner_type', User::class)->where('owner_id', $user->id)->exists()) {
            Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'code' => 'WAL' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
        }

        // Thêm user vào branch_user
        if (!$user->branches()->where('branch_id', $branchId)->exists()) {
            $user->branches()->attach($branchId);
        }

        // Tạo record trong bảng parents
        $parentRecord = ParentModel::create([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'is_active' => true,
        ]);
        Log::info('[UserCreation] ✅ Parent record created', ['parent_id' => $parentRecord->id]);

        // Update customer với user_id
        Log::info('[UserCreation] 🔗 Updating customer with user_id', [
            'customer_id' => $customer->id,
            'user_id' => $user->id,
        ]);
        
        $customer->update(['user_id' => $user->id]);
        
        // Verify update
        $customer = $customer->fresh();
        Log::info('[UserCreation] ✅ Customer user_id updated', [
            'customer_id' => $customer->id,
            'customer_user_id' => $customer->user_id,
        ]);

        Log::info("✅ Created parent user successfully", [
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'username' => $username,
            'default_password' => $defaultPassword,
        ]);

        return $user;
    }

    /**
     * Tạo user cho học viên (từ CustomerChild)
     */
    protected function createStudentUserFromChild(CustomerChild $child, int $branchId, Customer $parent): ?User
    {
        Log::info('[UserCreation] 🔵 createStudentUserFromChild called', [
            'child_id' => $child->id,
            'child_name' => $child->name,
            'child_user_id' => $child->user_id,
            'branch_id' => $branchId,
            'parent_id' => $parent->id,
        ]);
        
        // Kiểm tra xem child đã có user chưa
        if ($child->user_id) {
            Log::info('[UserCreation] ✅ Child already has user_id', ['user_id' => $child->user_id]);
            $existingUser = User::find($child->user_id);
            if ($existingUser) {
                // Đảm bảo có role user (không gán student - dựa trên bảng students)
                $this->ensureRole($existingUser, 'user');
                
                // Đảm bảo có trong branch_user
                if (!$existingUser->branches()->where('branch_id', $branchId)->exists()) {
                    $existingUser->branches()->attach($branchId);
                }
                
                // Đảm bảo có record trong students table
                if (!Student::where('user_id', $existingUser->id)->exists()) {
                    Log::info('[UserCreation] Creating student record for existing user', ['user_id' => $existingUser->id]);
                    $student = Student::create([
                        'user_id' => $existingUser->id,
                        'student_code' => $child->student_code ?: Student::generateStudentCode(),
                        'branch_id' => $branchId,
                        'enrollment_date' => now(),
                        'is_active' => true,
                    ]);
                    
                    // Link với parent
                    $parentRecord = ParentModel::where('user_id', $parent->user_id)->first();
                    if ($parentRecord && $student) {
                        $student->parents()->syncWithoutDetaching([$parentRecord->id => [
                            'relationship' => 'Parent',
                            'is_primary' => true,
                        ]]);
                    }
                }
                
                return $existingUser;
            }
        }

        // Tạo student code nếu chưa có
        if (!$child->student_code) {
            Log::info('[UserCreation] Generating student code for child', ['child_id' => $child->id]);
            $child->student_code = $this->generateStudentCode($child);
            $child->save();
        }

        // Sử dụng student_code làm username
        $username = strtolower($child->student_code);
        $email = "{$username}@school.local";
        
        // Check if user already exists with this email
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::info('[UserCreation] ✅ Found existing user with same email', [
                'user_id' => $existingUser->id,
                'email' => $email,
            ]);

            // Ensure has user role (không gán student - dựa trên bảng students)
            $this->ensureRole($existingUser, 'user');
            
            // Ensure in branch
            if (!$existingUser->branches()->where('branch_id', $branchId)->exists()) {
                $existingUser->branches()->attach($branchId);
            }
            
            // Ensure student record exists
            if (!Student::where('user_id', $existingUser->id)->exists()) {
                $student = Student::create([
                    'user_id' => $existingUser->id,
                    'student_code' => $child->student_code,
                    'branch_id' => $branchId,
                    'enrollment_date' => now(),
                    'is_active' => true,
                ]);
                
                // Link with parent
                $parentRecord = ParentModel::where('user_id', $parent->user_id)->first();
                if ($parentRecord && $student) {
                    $student->parents()->syncWithoutDetaching([$parentRecord->id => [
                        'relationship' => 'Parent',
                        'is_primary' => true,
                    ]]);
                }
            }
            
            // Update child
            $child->update(['user_id' => $existingUser->id]);
            
            return $existingUser;
        }
        
        // Tạo mật khẩu mặc định từ student code
        $defaultPassword = $this->generateDefaultPasswordFromCode($child->student_code);

        Log::info('[UserCreation] 📝 Creating new user for child', [
            'username' => $username,
            'email' => $email,
        ]);

        try {
            $user = User::create([
                'name' => $child->name,
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($defaultPassword),
                'phone' => null, // Child không có số điện thoại riêng
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
            
            Log::info('[UserCreation] ✅ User created successfully', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('[UserCreation] ❌ Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Gán role user (không gán student role - permissions dựa trên bảng students)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole && !$user->roles()->where('role_id', $userRole->id)->exists()) {
            $user->roles()->attach($userRole->id);
        }

        // Tạo wallet cho user
        if (!Wallet::where('owner_type', User::class)->where('owner_id', $user->id)->exists()) {
            Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'code' => 'WAL' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);
        }

        // Thêm user vào branch_user
        if (!$user->branches()->where('branch_id', $branchId)->exists()) {
            $user->branches()->attach($branchId);
        }

        // Tạo record trong bảng students
        $student = Student::create([
            'user_id' => $user->id,
            'student_code' => $child->student_code,
            'branch_id' => $branchId,
            'enrollment_date' => now(),
            'is_active' => true,
        ]);

        // Liên kết với parent (nếu parent đã có record trong bảng parents)
        $parentRecord = ParentModel::where('user_id', $parent->user_id)->first();
        if ($parentRecord && $student) {
            $student->parents()->attach($parentRecord->id, [
                'relationship' => 'Parent', // Có thể customize
                'is_primary' => true,
            ]);
        }

        // Update child với user_id
        Log::info('[UserCreation] 🔗 Updating child with user_id', [
            'child_id' => $child->id,
            'user_id' => $user->id,
        ]);
        
        $child->update(['user_id' => $user->id]);
        
        // Verify the update
        $child = $child->fresh();
        Log::info('[UserCreation] ✅ Child user_id updated', [
            'child_id' => $child->id,
            'child_user_id' => $child->user_id,
        ]);

        Log::info("✅ Created student user for child successfully", [
            'child_id' => $child->id,
            'parent_customer_id' => $parent->id,
            'user_id' => $user->id,
            'username' => $username,
            'student_code' => $child->student_code,
            'default_password' => $defaultPassword,
        ]);

        return $user;
    }

    /**
     * Đảm bảo user có role
     */
    protected function ensureRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }
    }

    /**
     * Generate username từ phone hoặc email
     */
    protected function generateUsername(?string $phone, ?string $email): string
    {
        if ($phone) {
            // Sử dụng 9 số cuối của phone
            return 'u' . substr($phone, -9);
        }
        
        if ($email) {
            return strtolower(explode('@', $email)[0]);
        }

        // Fallback: random
        return 'user_' . Str::random(8);
    }

    /**
     * Generate mật khẩu mặc định từ phone (6 số cuối)
     */
    protected function generateDefaultPassword(?string $phone): string
    {
        if ($phone && strlen($phone) >= 6) {
            return substr($phone, -6);
        }
        
        return '123456'; // Fallback
    }

    /**
     * Generate student code
     */
    protected function generateStudentCode(CustomerChild $child): string
    {
        $prefix = 'ST';
        $year = date('Y');
        $lastCode = CustomerChild::where('student_code', 'like', "{$prefix}{$year}%")
            ->orderBy('student_code', 'desc')
            ->first();

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode->student_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate mật khẩu mặc định từ student code
     */
    protected function generateDefaultPasswordFromCode(string $studentCode): string
    {
        // Lấy 6 ký tự cuối của student code
        return substr($studentCode, -6);
    }
}

