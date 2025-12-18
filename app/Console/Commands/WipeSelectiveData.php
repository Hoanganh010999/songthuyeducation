<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class WipeSelectiveData extends Command
{
    protected $signature = 'db:wipe-selective 
                            {--force : Force wipe without confirmation}';

    protected $description = 'Wipe only student/class/attendance data, preserve settings/translations';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will DELETE students, classes, attendance. Preserve settings/translations. Continue?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        $this->info('🔄 Starting selective wipe...');

        DB::beginTransaction();
        
        try {
            // Preserve admin
            $admin = User::where('email', 'admin@songthuy.edu.vn')->first();
            
            if (!$admin) {
                $this->warn('Admin not found. Creating...');
                $admin = $this->createAdmin();
            } else {
                $this->info('✓ Found admin: ' . $admin->email);
                $admin->password = Hash::make('2K3h0o1n9g@');
                $admin->save();
            }

            $adminId = $admin->id;

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Tables to wipe
            $tablesToWipe = [
                'attendances',
                'class_lesson_sessions',
                'class_students',
                'class_schedules',
                'classes',
                'parent_student',
                'students',
                'parents',
                'customer_children',
                'customer_interactions',
                'customers',
                'enrollments',
                'wallet_transactions',
                'wallets',
                'subjects',
                'branches',
            ];

            $this->info("Will wipe " . count($tablesToWipe) . " tables");

            $bar = $this->output->createProgressBar(count($tablesToWipe));
            $bar->start();

            foreach ($tablesToWipe as $table) {
                try {
                    DB::table($table)->truncate();
                } catch (\Exception $e) {
                    DB::table($table)->delete();
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Also delete users that are not admin
            $this->info('Cleaning up users...');
            
            // Method 1: Delete by role
            $rolesToDelete = ['teacher', 'student', 'parent'];
            $userIdsByRole = DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->whereIn('roles.name', $rolesToDelete)
                ->where('role_user.user_id', '!=', $adminId)
                ->pluck('role_user.user_id')
                ->unique();
            
            // Method 2: Delete by email pattern (for users without roles)
            $userIdsByEmail = User::where('id', '!=', $adminId)
                ->where(function($q) {
                    $q->where('email', 'like', 'std%@songthuy.edu.vn')
                      ->orWhere('email', 'like', 'ph%@songthuy.edu.vn')
                      ->orWhere('email', 'like', 'cust%@songthuy.edu.vn')
                      ->orWhere('email', 'like', 'student_%@songthuy.edu.vn')
                      ->orWhere('email', 'like', 'parent_%@songthuy.edu.vn')
                      ->orWhere('email', 'like', 'customer_%@songthuy.edu.vn');
                })
                ->pluck('id');
            
            // Merge both lists
            $userIdsToDelete = $userIdsByRole->merge($userIdsByEmail)->unique();

            if ($userIdsToDelete->count() > 0) {
                DB::table('role_user')->whereIn('user_id', $userIdsToDelete)->delete();
                DB::table('branch_user')->whereIn('user_id', $userIdsToDelete)->delete();
                User::whereIn('id', $userIdsToDelete)->delete();
                $this->info("✓ Deleted {$userIdsToDelete->count()} users");
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            $this->newLine();
            $this->info('✅ Selective wipe completed!');
            $this->newLine();
            $this->table(
                ['Preserved', 'Status'],
                [
                    ['Admin account', '✓'],
                    ['Settings', '✓'],
                    ['Translations', '✓'],
                    ['Languages', '✓'],
                    ['Permissions', '✓'],
                    ['Roles', '✓'],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function createAdmin()
    {
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['description' => 'Super Administrator']
        );

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@songthuy.edu.vn',
            'password' => Hash::make('2K3h0o1n9g@'),
            'email_verified_at' => now(),
        ]);

        $admin->roles()->attach($superAdminRole->id);

        $this->info('✓ Created new admin');

        return $admin;
    }
}

