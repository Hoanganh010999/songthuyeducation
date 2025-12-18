<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamSubjectCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Get all exam subjects
        $examSubjects = DB::table('exam_subjects')
            ->where('is_active', true)
            ->get();

        foreach ($examSubjects as $subject) {
            // Check if categories already exist
            $existingCount = DB::table('exam_subject_categories')
                ->where('subject_id', $subject->id)
                ->count();

            if ($existingCount > 0) {
                $this->command->info("Categories for {$subject->name} already exist, skipping...");
                continue; // Skip if categories already exist
            }

            // Create standard English skill categories
            $categories = [
                [
                    'subject_id' => $subject->id,
                    'name' => 'Reading',
                    'code' => 'reading',
                    'description' => 'Reading comprehension exercises',
                    'icon' => '📖',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'subject_id' => $subject->id,
                    'name' => 'Writing',
                    'code' => 'writing',
                    'description' => 'Writing exercises',
                    'icon' => '✍️',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'subject_id' => $subject->id,
                    'name' => 'Listening',
                    'code' => 'listening',
                    'description' => 'Listening comprehension exercises',
                    'icon' => '🎧',
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'subject_id' => $subject->id,
                    'name' => 'Speaking',
                    'code' => 'speaking',
                    'description' => 'Speaking exercises',
                    'icon' => '🗣️',
                    'sort_order' => 4,
                    'is_active' => true,
                ],
                [
                    'subject_id' => $subject->id,
                    'name' => 'Grammar',
                    'code' => 'grammar',
                    'description' => 'Grammar exercises',
                    'icon' => '📝',
                    'sort_order' => 5,
                    'is_active' => true,
                ],
                [
                    'subject_id' => $subject->id,
                    'name' => 'Vocabulary',
                    'code' => 'vocabulary',
                    'description' => 'Vocabulary exercises',
                    'icon' => '📚',
                    'sort_order' => 6,
                    'is_active' => true,
                ],
            ];

            foreach ($categories as $category) {
                DB::table('exam_subject_categories')->insert(array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $this->command->info('Exam subject categories seeded successfully!');
    }
}
