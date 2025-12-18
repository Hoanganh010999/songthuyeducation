<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_code',
        'branch_id',
        'enrollment_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship', 'is_primary')
            ->withTimestamps();
    }

    public function classes()
    {
        // class_students.student_id stores user_id, not students.id
        // So we join on students.user_id = class_students.student_id
        return $this->belongsToMany(ClassModel::class, 'class_students', 'student_id', 'class_id', 'user_id')
            ->using(ClassStudent::class)
            ->withPivot('enrollment_date', 'status', 'discount_percent', 'notes')
            ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'user_id');
    }

    public function enrollments()
    {
        return Enrollment::where('student_type', Student::class)
            ->where('student_id', $this->id)
            ->get();
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Get the customer record if this student is also a customer
     */
    public function customer()
    {
        return $this->hasOneThrough(
            Customer::class,
            User::class,
            'id', // Foreign key on users table
            'user_id', // Foreign key on customers table
            'user_id', // Local key on students table
            'id' // Local key on users table
        );
    }

    /**
     * Get effective wallet balance (prioritize customer wallet if exists)
     */
    public function getEffectiveBalance()
    {
        // Check if this student is also a customer
        $customer = Customer::where('user_id', $this->user_id)->first();
        
        if ($customer) {
            $customerWallet = Wallet::where('owner_type', Customer::class)
                ->where('owner_id', $customer->id)
                ->first();
            
            if ($customerWallet && $customerWallet->balance > 0) {
                return $customerWallet->balance;
            }
        }
        
        // Fallback to student wallet
        return $this->wallet ? $this->wallet->balance : 0;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Methods
     */
    public static function generateStudentCode(): string
    {
        $prefix = 'STD';
        $year = date('Y');
        
        $lastStudent = self::where('student_code', 'like', "{$prefix}{$year}%")
            ->orderBy('student_code', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_code, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->student_code)) {
                $student->student_code = self::generateStudentCode();
            }
        });
    }
}
