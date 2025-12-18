<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerInteractionTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            // English
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interaction_history', 'value' => 'Interaction History'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'add_interaction', 'value' => 'Add Interaction'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'no_interactions', 'value' => 'No interaction history yet'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interaction_date', 'value' => 'Interaction Date'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interaction_type', 'value' => 'Interaction Type'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'interaction_result', 'value' => 'Result'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'notes', 'value' => 'Notes'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'notes_placeholder', 'value' => 'Enter notes about this interaction...'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'next_follow_up', 'value' => 'Next Follow-up'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'next_follow_up_hint', 'value' => 'Optional: Set a reminder for next contact'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'customer', 'value' => 'Customer'],
            ['language_id' => 2, 'group' => 'customers', 'key' => 'latest_interaction', 'value' => 'Latest Interaction'],
            
            // Vietnamese
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interaction_history', 'value' => 'Lịch Sử Tương Tác'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'add_interaction', 'value' => 'Thêm Tương Tác'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'no_interactions', 'value' => 'Chưa có lịch sử tương tác'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interaction_date', 'value' => 'Ngày Tương Tác'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interaction_type', 'value' => 'Loại Tương Tác'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'interaction_result', 'value' => 'Kết Quả'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'notes', 'value' => 'Ghi Chú'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'notes_placeholder', 'value' => 'Nhập ghi chú về lần tương tác này...'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'next_follow_up', 'value' => 'Hẹn Liên Hệ Lại'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'next_follow_up_hint', 'value' => 'Tùy chọn: Đặt lịch nhắc liên hệ lại'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'customer', 'value' => 'Khách Hàng'],
            ['language_id' => 1, 'group' => 'customers', 'key' => 'latest_interaction', 'value' => 'Tương Tác Gần Nhất'],
        ];

        foreach ($translations as $translation) {
            DB::table('translations')->updateOrInsert(
                [
                    'language_id' => $translation['language_id'],
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                ],
                ['value' => $translation['value']]
            );
        }

        $this->command->info('✅ Customer Interaction translations seeded successfully!');
    }
}
