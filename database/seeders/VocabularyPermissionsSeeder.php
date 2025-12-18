<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VocabularyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'module' => 'vocabulary',
                'action' => 'view',
                'name' => 'vocabulary.view',
                'display_name' => 'View Vocabulary',
                'description' => 'Can view vocabulary entries',
                'sort_order' => 1
            ],
            [
                'module' => 'vocabulary',
                'action' => 'create',
                'name' => 'vocabulary.create',
                'display_name' => 'Create Vocabulary',
                'description' => 'Can create new vocabulary entries',
                'sort_order' => 2
            ],
            [
                'module' => 'vocabulary',
                'action' => 'edit',
                'name' => 'vocabulary.edit',
                'display_name' => 'Edit Vocabulary',
                'description' => 'Can edit vocabulary entries',
                'sort_order' => 3
            ],
            [
                'module' => 'vocabulary',
                'action' => 'delete',
                'name' => 'vocabulary.delete',
                'display_name' => 'Delete Vocabulary',
                'description' => 'Can delete vocabulary entries',
                'sort_order' => 4
            ],
            [
                'module' => 'vocabulary',
                'action' => 'record_pronunciation',
                'name' => 'vocabulary.record_pronunciation',
                'display_name' => 'Record Pronunciation',
                'description' => 'Can record and check pronunciation',
                'sort_order' => 5
            ]
        ];

        // Insert permissions
        foreach ($permissions as $permData) {
            Permission::updateOrCreate(
                ['name' => $permData['name']],
                $permData
            );
        }

        $this->command->info('✅ Vocabulary permissions created successfully!');
        $this->command->info('   Total permissions added: ' . count($permissions));
        $this->command->info('⚠️  Please assign these permissions to roles manually in the Admin panel.');
    }
}
