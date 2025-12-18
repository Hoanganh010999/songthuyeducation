<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Language;
use App\Models\Translation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $english = Language::where('code', 'en')->first();
        $vietnamese = Language::where('code', 'vi')->first();

        if (!$english || !$vietnamese) {
            return;
        }

        $translations = [
            'writing_grading' => ['Writing Grading', 'Chấm bài viết'],
            'clear_all_annotations' => ['Clear All', 'Xóa tất cả'],
            'delete' => ['Delete', 'Xóa'],
            'error' => ['Error', 'Lỗi'],
            'missing' => ['Missing', 'Thiếu'],
            'annotations_summary' => ['Annotations', 'Tóm tắt đánh dấu'],
            'mark_as_delete' => ['Mark as Delete', 'Đánh dấu xóa'],
            'mark_error' => ['Mark Error', 'Đánh dấu lỗi'],
            'insert_missing' => ['Insert Missing Word', 'Chèn từ thiếu'],
            'select_error_type' => ['Select Error Type', 'Chọn loại lỗi'],
            'no_error_types' => ['No error types found', 'Không tìm thấy loại lỗi'],
            'missing_word' => ['Missing Word/Phrase', 'Từ/Cụm từ thiếu'],
            'missing_word_hint' => ['Enter the word or phrase that should be inserted between the selected words:', 'Nhập từ hoặc cụm từ cần chèn vào giữa các từ đã chọn:'],
            'missing_word_placeholder' => ['Enter suggestion...', 'Nhập gợi ý...'],
            'error_comment_placeholder' => ['Enter comment about the error...', 'Nhập nhận xét về lỗi...'],
            'delete_comment' => ['Delete Comment', 'Nhận xét về nội dung cần xóa'],
            'delete_comment_hint' => ['Enter the reason why this text should be deleted:', 'Nhập lý do tại sao cần xóa phần text này:'],
            'delete_comment_placeholder' => ['E.g.: Unnecessary, repetitive...', 'Ví dụ: Không cần thiết, lặp lại ý...'],
        ];

        foreach ($translations as $key => [$enValue, $viValue]) {
            Translation::updateOrCreate(
                ['language_id' => $english->id, 'group' => 'course', 'key' => $key],
                ['value' => $enValue]
            );
            Translation::updateOrCreate(
                ['language_id' => $vietnamese->id, 'group' => 'course', 'key' => $key],
                ['value' => $viValue]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $english = Language::where('code', 'en')->first();
        $vietnamese = Language::where('code', 'vi')->first();

        if (!$english || !$vietnamese) {
            return;
        }

        $keys = [
            'writing_grading',
            'clear_all_annotations',
            'delete',
            'error',
            'missing',
            'annotations_summary',
            'mark_as_delete',
            'mark_error',
            'insert_missing',
            'select_error_type',
            'no_error_types',
            'missing_word',
            'missing_word_hint',
            'missing_word_placeholder',
            'error_comment_placeholder',
            'delete_comment',
            'delete_comment_hint',
            'delete_comment_placeholder',
        ];

        foreach ($keys as $key) {
            Translation::where('group', 'course')
                ->where('key', $key)
                ->whereIn('language_id', [$english->id, $vietnamese->id])
                ->delete();
        }
    }
};
