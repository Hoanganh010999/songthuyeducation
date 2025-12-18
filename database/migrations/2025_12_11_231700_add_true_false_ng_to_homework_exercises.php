<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'true_false_ng' to the type ENUM
        DB::statement("ALTER TABLE `homework_exercises` MODIFY COLUMN `type` ENUM(
            'multiple_choice',
            'multiple_answers',
            'true_false',
            'true_false_ng',
            'yes_no',
            'yes_no_ng',
            'fill_blanks',
            'fill_blanks_select',
            'matching',
            'matching_headings',
            'matching_features',
            'matching_sentence_endings',
            'ordering',
            'sentence_completion',
            'summary_completion',
            'note_completion',
            'table_completion',
            'flow_chart_completion',
            'diagram_labeling',
            'short_answer',
            'essay',
            'audio_response',
            'drag_drop',
            'hotspot',
            'labeling'
        ) NOT NULL DEFAULT 'multiple_choice'");
    }

    public function down(): void
    {
        // Remove 'true_false_ng' from the type ENUM
        DB::statement("ALTER TABLE `homework_exercises` MODIFY COLUMN `type` ENUM(
            'multiple_choice',
            'multiple_answers',
            'true_false',
            'yes_no',
            'yes_no_ng',
            'fill_blanks',
            'fill_blanks_select',
            'matching',
            'matching_headings',
            'matching_features',
            'matching_sentence_endings',
            'ordering',
            'sentence_completion',
            'summary_completion',
            'note_completion',
            'table_completion',
            'flow_chart_completion',
            'diagram_labeling',
            'short_answer',
            'essay',
            'audio_response',
            'drag_drop',
            'hotspot',
            'labeling'
        ) NOT NULL DEFAULT 'multiple_choice'");
    }
};
