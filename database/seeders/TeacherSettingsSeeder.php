<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QualitySetting;
use App\Models\Branch;

class TeacherSettingsSeeder extends Seeder
{
    /**
     * Seed teacher position code settings for branches
     */
    public function run(): void
    {
        // Position codes to filter teachers
        $positionCodes = ['GV01', 'GV02', 'GV03', 'GVTT', 'GVCN'];

        // Setup for branches 1, 2, 3
        for ($branchId = 1; $branchId <= 3; $branchId++) {
            $branch = Branch::find($branchId);
            
            if (!$branch) {
                $this->command->warn("Branch {$branchId} not found, skipping...");
                continue;
            }

            QualitySetting::updateOrCreate(
                [
                    'branch_id' => $branchId,
                    'industry' => 'education',
                    'setting_key' => 'teacher_position_codes'
                ],
                [
                    'setting_value' => $positionCodes
                ]
            );

            $this->command->info("Setup teacher position codes for branch: {$branch->name}");
            $this->command->info("  Position codes: " . implode(', ', $positionCodes));
        }

        $this->command->info('Teacher settings seeded successfully!');
    }
}

