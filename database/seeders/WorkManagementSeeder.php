<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkItem;
use App\Models\WorkTag;
use App\Models\WorkAssignment;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use Carbon\Carbon;

class WorkManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Work Management data...');

        // Get first branch
        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('No branch found! Please create a branch first.');
            return;
        }

        // Get admin user
        $admin = User::where('email', 'admin@example.com')->first() ?? User::first();
        if (!$admin) {
            $this->command->error('No user found!');
            return;
        }

        // Get users
        $users = User::limit(5)->get();

        // Create tags
        $this->command->info('Creating tags...');
        $tags = [];
        $tagData = [
            ['name' => 'Frontend', 'color' => '#3B82F6'],
            ['name' => 'Backend', 'color' => '#10B981'],
            ['name' => 'Database', 'color' => '#F59E0B'],
            ['name' => 'UI/UX', 'color' => '#8B5CF6'],
            ['name' => 'Bug Fix', 'color' => '#EF4444'],
            ['name' => 'Feature', 'color' => '#06B6D4'],
        ];

        foreach ($tagData as $data) {
            $tags[] = WorkTag::create([
                'name' => $data['name'],
                'branch_id' => $branch->id,
                'color' => $data['color'],
            ]);
        }

        // Create projects
        $this->command->info('Creating projects...');

        $project1 = WorkItem::create([
            'type' => WorkItem::TYPE_PROJECT,
            'title' => 'Xây dựng Module Quản lý Công việc',
            'description' => 'Phát triển module quản lý công việc tích hợp với HAY System',
            'priority' => WorkItem::PRIORITY_HIGH,
            'status' => WorkItem::STATUS_IN_PROGRESS,
            'start_date' => Carbon::now()->subDays(10),
            'due_date' => Carbon::now()->addDays(60),
            'estimated_hours' => 320,
            'branch_id' => $branch->id,
            'created_by' => $admin->id,
            'metadata' => [
                'difficulty_level' => 'high',
                'complexity' => 8,
                'impact' => 'organization'
            ]
        ]);

        $project1->tags()->attach([$tags[1]->id, $tags[5]->id]); // Backend, Feature

        // Assign to project
        WorkAssignment::create([
            'work_item_id' => $project1->id,
            'user_id' => $admin->id,
            'role' => WorkAssignment::ROLE_ASSIGNER,
            'assigned_at' => now(),
            'assigned_by' => $admin->id,
            'status' => WorkAssignment::STATUS_ACCEPTED,
        ]);

        // Create tasks for project 1
        $this->command->info('Creating tasks...');

        $tasks = [
            [
                'title' => 'Thiết kế database schema',
                'description' => 'Tạo migrations và models cho Work Management',
                'priority' => WorkItem::PRIORITY_URGENT,
                'status' => WorkItem::STATUS_COMPLETED,
                'estimated_hours' => 16,
                'actual_hours' => 14,
                'completed_at' => Carbon::now()->subDays(8),
                'tags' => [$tags[2]->id], // Database
            ],
            [
                'title' => 'Xây dựng API endpoints',
                'description' => 'Tạo controllers và routes cho module',
                'priority' => WorkItem::PRIORITY_HIGH,
                'status' => WorkItem::STATUS_IN_PROGRESS,
                'estimated_hours' => 24,
                'actual_hours' => null,
                'completed_at' => null,
                'tags' => [$tags[1]->id], // Backend
            ],
            [
                'title' => 'Tạo giao diện người dùng',
                'description' => 'Phát triển Vue components cho Work Management',
                'priority' => WorkItem::PRIORITY_MEDIUM,
                'status' => WorkItem::STATUS_PENDING,
                'estimated_hours' => 40,
                'actual_hours' => null,
                'completed_at' => null,
                'tags' => [$tags[0]->id, $tags[3]->id], // Frontend, UI/UX
            ],
            [
                'title' => 'Tích hợp Google Drive',
                'description' => 'Kết nối upload files lên Google Drive',
                'priority' => WorkItem::PRIORITY_MEDIUM,
                'status' => WorkItem::STATUS_ASSIGNED,
                'estimated_hours' => 16,
                'actual_hours' => null,
                'completed_at' => null,
                'tags' => [$tags[1]->id], // Backend
            ],
        ];

        foreach ($tasks as $index => $taskData) {
            $tags = $taskData['tags'];
            unset($taskData['tags']);

            $task = WorkItem::create(array_merge([
                'type' => WorkItem::TYPE_TASK,
                'parent_id' => $project1->id,
                'start_date' => Carbon::now()->subDays(10 - $index),
                'due_date' => Carbon::now()->addDays(5 + ($index * 10)),
                'branch_id' => $branch->id,
                'created_by' => $admin->id,
                'assigned_by' => $admin->id,
                'metadata' => [
                    'difficulty_level' => 'medium',
                    'complexity' => 5 + $index,
                ]
            ], $taskData));

            $task->tags()->attach($tags);

            // Assign random user as executor
            if ($users->count() > 0) {
                $executor = $users->random();
                WorkAssignment::create([
                    'work_item_id' => $task->id,
                    'user_id' => $executor->id,
                    'role' => WorkAssignment::ROLE_EXECUTOR,
                    'assigned_at' => now(),
                    'assigned_by' => $admin->id,
                    'status' => WorkAssignment::STATUS_ACCEPTED,
                ]);

                // Add assigner
                WorkAssignment::create([
                    'work_item_id' => $task->id,
                    'user_id' => $admin->id,
                    'role' => WorkAssignment::ROLE_ASSIGNER,
                    'assigned_at' => now(),
                    'assigned_by' => $admin->id,
                    'status' => WorkAssignment::STATUS_ACCEPTED,
                ]);
            }
        }

        // Create another standalone project
        $project2 = WorkItem::create([
            'type' => WorkItem::TYPE_PROJECT,
            'title' => 'Triển khai HAY System',
            'description' => 'Xây dựng hệ thống đánh giá hiệu suất theo HAY',
            'priority' => WorkItem::PRIORITY_HIGH,
            'status' => WorkItem::STATUS_PENDING,
            'start_date' => Carbon::now()->addDays(30),
            'due_date' => Carbon::now()->addDays(90),
            'estimated_hours' => 240,
            'branch_id' => $branch->id,
            'created_by' => $admin->id,
            'metadata' => [
                'difficulty_level' => 'very_high',
                'complexity' => 10,
                'impact' => 'organization'
            ]
        ]);

        $project2->tags()->attach([$tags[1]->id, $tags[5]->id]);

        WorkAssignment::create([
            'work_item_id' => $project2->id,
            'user_id' => $admin->id,
            'role' => WorkAssignment::ROLE_ASSIGNER,
            'assigned_at' => now(),
            'assigned_by' => $admin->id,
            'status' => WorkAssignment::STATUS_ACCEPTED,
        ]);

        $this->command->info('Work Management seeding completed!');
        $this->command->info('Created:');
        $this->command->info('- 2 Projects');
        $this->command->info('- 4 Tasks');
        $this->command->info('- 6 Tags');
    }
}
