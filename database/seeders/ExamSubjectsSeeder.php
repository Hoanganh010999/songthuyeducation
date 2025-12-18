<?php

namespace Database\Seeders;

use App\Models\Examination\ExamSubject;
use App\Models\Examination\ExamSubjectCategory;
use Illuminate\Database\Seeder;

class ExamSubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = ExamSubject::getDefaultSubjects();

        foreach ($subjects as $index => $subjectData) {
            $categories = $subjectData['categories'] ?? [];
            unset($subjectData['categories']);

            $subject = ExamSubject::updateOrCreate(
                ['code' => $subjectData['code']],
                array_merge($subjectData, ['sort_order' => $index])
            );

            foreach ($categories as $catIndex => $categoryData) {
                ExamSubjectCategory::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'code' => $categoryData['code'],
                    ],
                    array_merge($categoryData, [
                        'subject_id' => $subject->id,
                        'sort_order' => $catIndex,
                    ])
                );
            }
        }

        $this->command->info('Exam subjects and categories seeded successfully!');
    }
}
