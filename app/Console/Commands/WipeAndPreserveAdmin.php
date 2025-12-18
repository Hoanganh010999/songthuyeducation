<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class WipeAndPreserveAdmin extends Command
{
    protected $signature = 'db:wipe-preserve-admin 
                            {--force : Force wipe without confirmation}';

    protected $description = 'Wipe all data but preserve superadmin account';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will DELETE ALL DATA except superadmin. Continue?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }

        $this->info('🔄 Starting database wipe...');

        DB::beginTransaction();
        
        try {
            // 1. Find and preserve admin
            $admin = User::where('email', 'admin@songthuy.edu.vn')->first();
            
            if (!$admin) {
                $this->warn('Admin not found. Creating new one...');
                $admin = $this->createAdmin();
            } else {
                $this->info('✓ Found admin: ' . $admin->email);
                
                // Update password to ensure it's correct
                $admin->password = Hash::make('2K3h0o1n9g@');
                $admin->save();
                $this->info('✓ Admin password updated');
            }

            $adminId = $admin->id;
            $superAdminRoleId = Role::where('name', 'super-admin')->value('id');

            // 2. Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 3. Get all tables
            $tables = collect($this->getTables());
            
            $this->info("Found {$tables->count()} tables to clean");

            // 4. Wipe each table (except migrations)
            $bar = $this->output->createProgressBar($tables->count());
            $bar->start();

            foreach ($tables as $table) {
                // Get table name (different format for SQLite vs MySQL)
                if (is_string($table)) {
                    $tableName = $table;
                } elseif (isset($table->name)) {
                    $tableName = $table->name;
                } else {
                    // For MySQL: Tables_in_xxx
                    $props = get_object_vars($table);
                    $tableName = reset($props);
                }
                
                // Skip these tables
                if (in_array($tableName, ['migrations', 'password_reset_tokens', 'sessions'])) {
                    $bar->advance();
                    continue;
                }

                // Special handling for users table
                if ($tableName === 'users') {
                    DB::table('users')->where('id', '!=', $adminId)->delete();
                    $bar->advance();
                    continue;
                }

                // Special handling for roles table
                if ($tableName === 'roles') {
                    DB::table('roles')->where('id', '!=', $superAdminRoleId)->delete();
                    $bar->advance();
                    continue;
                }

                // Special handling for role_user
                if ($tableName === 'role_user') {
                    DB::table('role_user')
                        ->where('user_id', '!=', $adminId)
                        ->orWhere('role_id', '!=', $superAdminRoleId)
                        ->delete();
                    
                    // Ensure admin has super-admin role
                    DB::table('role_user')->updateOrInsert(
                        ['user_id' => $adminId, 'role_id' => $superAdminRoleId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                    $bar->advance();
                    continue;
                }

                // For all other tables, truncate
                try {
                    DB::table($tableName)->truncate();
                } catch (\Exception $e) {
                    // If truncate fails, try delete
                    DB::table($tableName)->delete();
                }
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // 5. Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            $this->newLine();
            $this->info('✅ Database wiped successfully!');
            $this->newLine();
            $this->info('🔐 Preserved admin account:');
            $this->table(
                ['Email', 'Password', 'Role'],
                [['admin@songthuy.edu.vn', '2K3h0o1n9g@', 'super-admin']]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function getTables()
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        
        if (config('database.default') === 'sqlite') {
            return DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        } else {
            return DB::select('SHOW TABLES');
        }
    }

    private function createAdmin()
    {
        // Create super-admin role if not exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['description' => 'Super Administrator with all permissions']
        );

        // Create admin user
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@songthuy.edu.vn',
            'password' => Hash::make('2K3h0o1n9g@'),
            'email_verified_at' => now(),
        ]);

        // Assign super-admin role
        $admin->roles()->attach($superAdminRole->id);

        $this->info('✓ Created new admin account');

        return $admin;
    }
}

