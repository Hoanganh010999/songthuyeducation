<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VocabularyTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $viLang = DB::table('languages')->where('code', 'vi')->first();
        $enLang = DB::table('languages')->where('code', 'en')->first();

        if (!$viLang || !$enLang) {
            $this->command->error('Languages not found!');
            return;
        }

        $translations = [
            // Main page headers
            'vocabulary.title' => ['vi' => 'Sổ Từ Vựng', 'en' => 'My Vocabulary Book'],
            'vocabulary.subtitle' => ['vi' => 'Sổ từ vựng cá nhân của bạn', 'en' => 'Your personal vocabulary collection'],
            
            // Navigation buttons
            'vocabulary.statistics' => ['vi' => 'Thống Kê', 'en' => 'Statistics'],
            'vocabulary.review_flashcards' => ['vi' => 'Ôn Tập Flashcards', 'en' => 'Review Flashcards'],
            
            // Search and filters
            'vocabulary.search_placeholder' => ['vi' => 'Tìm kiếm từ hoặc định nghĩa...', 'en' => 'Search words or definitions...'],
            'vocabulary.all_levels' => ['vi' => 'Tất Cả Mức Độ', 'en' => 'All Levels'],
            'vocabulary.level_new' => ['vi' => 'Mới (Cấp 0)', 'en' => 'New (Level 0)'],
            'vocabulary.level_beginner' => ['vi' => 'Mới Học (Cấp 1)', 'en' => 'Beginner (Level 1)'],
            'vocabulary.level_learning' => ['vi' => 'Đang Học (Cấp 2)', 'en' => 'Learning (Level 2)'],
            'vocabulary.level_practicing' => ['vi' => 'Luyện Tập (Cấp 3)', 'en' => 'Practicing (Level 3)'],
            'vocabulary.level_good' => ['vi' => 'Tốt (Cấp 4)', 'en' => 'Good (Level 4)'],
            'vocabulary.level_mastered' => ['vi' => 'Thành Thạo (Cấp 5)', 'en' => 'Mastered (Level 5)'],
            
            // Table headers
            'vocabulary.word_wordform' => ['vi' => 'Từ / Dạng Từ', 'en' => 'Word / Word Form'],
            'vocabulary.synonym_antonym' => ['vi' => 'Từ Đồng/Trái Nghĩa', 'en' => 'Synonym / Antonym'],
            'vocabulary.definition' => ['vi' => 'Định Nghĩa', 'en' => 'Definition'],
            'vocabulary.example' => ['vi' => 'Ví Dụ', 'en' => 'Example'],
            'vocabulary.actions' => ['vi' => 'Hành Động', 'en' => 'Actions'],
            
            // Table actions
            'vocabulary.save' => ['vi' => 'Lưu', 'en' => 'Save'],
            'vocabulary.cancel' => ['vi' => 'Hủy', 'en' => 'Cancel'],
            'vocabulary.edit' => ['vi' => 'Sửa', 'en' => 'Edit'],
'vocabulary.delete' => ['vi' => 'Xóa', 'en' => 'Delete'],
            
            // Empty states and loading
            'vocabulary.loading' => ['vi' => 'Đang tải...', 'en' => 'Loading...'],
            'vocabulary.no_entries' => ['vi' => 'Chưa có từ vựng nào', 'en' => 'No vocabulary entries yet'],
            'vocabulary.add_first_word' => ['vi' => 'Nhấn nút + để thêm từ đầu tiên!', 'en' => 'Click the + button to add your first word!'],
            
            // Placeholders
            'vocabulary.placeholder_word' => ['vi' => 'Từ tiếng Anh', 'en' => 'English word'],
            'vocabulary.placeholder_wordform' => ['vi' => 'noun, verb, adjective...', 'en' => 'noun, verb, adjective...'],
            'vocabulary.placeholder_synonym' => ['vi' => 'Từ đồng nghĩa...', 'en' => 'Synonyms...'],
            'vocabulary.placeholder_antonym' => ['vi' => 'Từ trái nghĩa...', 'en' => 'Antonyms...'],
            'vocabulary.placeholder_definition' => ['vi' => 'Định nghĩa...', 'en' => 'Definition...'],
            'vocabulary.placeholder_example' => ['vi' => 'Câu ví dụ...', 'en' => 'Example sentence...'],
            
            // Spelling check
            'vocabulary.spelling_error' => ['vi' => 'Lỗi chính tả. Bạn có muốn nói:', 'en' => 'Spelling error. Did you mean:'],
            'vocabulary.pronunciation' => ['vi' => 'Phát Âm', 'en' => 'Pronunciation'],
            
            // Delete confirmation
            'vocabulary.delete_confirm_title' => ['vi' => 'Xóa từ này?', 'en' => 'Delete this word?'],
            'vocabulary.delete_confirm_text' => ['vi' => 'Hành động này không thể hoàn tác', 'en' => 'This action cannot be undone'],
            'vocabulary.deleted_success' => ['vi' => 'Đã xóa từ khỏi sổ từ vựng', 'en' => 'Word has been removed from your vocabulary book'],
            
            // Statistics modal
            'vocabulary.stats_title' => ['vi' => 'Thống Kê Từ Vựng', 'en' => 'Vocabulary Statistics'],
            'vocabulary.total_words' => ['vi' => 'Tổng Số Từ:', 'en' => 'Total Words:'],
            'vocabulary.reviewed' => ['vi' => 'Đã Ôn Tập:', 'en' => 'Reviewed:'],
            'vocabulary.mastered_count' => ['vi' => 'Thành Thạo (Cấp 4+):', 'en' => 'Mastered (Level 4+):'],
            'vocabulary.mastery_distribution' => ['vi' => 'Phân Bố Mức Độ:', 'en' => 'Mastery Distribution:'],
            'vocabulary.level' => ['vi' => 'Cấp', 'en' => 'Level'],
            'vocabulary.close' => ['vi' => 'Đóng', 'en' => 'Close'],
            
            // Flashcard review
            'vocabulary.flashcard_review' => ['vi' => 'Ôn Tập Flashcard', 'en' => 'Flashcard Review'],
            'vocabulary.mode_word_to_meaning' => ['vi' => 'Từ → Nghĩa', 'en' => 'Word → Meaning'],
            'vocabulary.mode_meaning_to_word' => ['vi' => 'Nghĩa → Từ', 'en' => 'Meaning → Word'],
            'vocabulary.loading_flashcards' => ['vi' => 'Đang tải flashcards...', 'en' => 'Loading flashcards...'],
            'vocabulary.click_to_reveal_meaning' => ['vi' => 'Nhấn để hiện nghĩa', 'en' => 'Click to reveal meaning'],
            'vocabulary.click_to_reveal_word' => ['vi' => 'Nhấn để hiện từ', 'en' => 'Click to reveal word'],
            'vocabulary.whats_the_word' => ['vi' => 'Từ này là gì?', 'en' => 'What\'s the word?'],
            'vocabulary.the_word_is' => ['vi' => 'Từ này là:', 'en' => 'The word is:'],
            
            // Flashcard hints
            'vocabulary.synonym' => ['vi' => 'Từ Đồng Nghĩa', 'en' => 'Synonym'],
            'vocabulary.antonym' => ['vi' => 'Từ Trái Nghĩa', 'en' => 'Antonym'],
            'vocabulary.example_hint' => ['vi' => 'Ví Dụ', 'en' => 'Example'],
            
            // Flashcard navigation
            'vocabulary.previous' => ['vi' => 'Trước', 'en' => 'Previous'],
            'vocabulary.next' => ['vi' => 'Tiếp', 'en' => 'Next'],
            'vocabulary.wrong' => ['vi' => 'Sai', 'en' => 'Wrong'],
            'vocabulary.correct' => ['vi' => 'Đúng', 'en' => 'Correct'],
            
            // Session complete
            'vocabulary.session_complete' => ['vi' => 'Hoàn Thành Buổi Học!', 'en' => 'Session Complete!'],
            'vocabulary.reviewed_all_words' => ['vi' => 'Bạn đã ôn tập {count} từ', 'en' => 'You\'ve reviewed all {count} words'],
            'vocabulary.correct_count' => ['vi' => 'Đúng:', 'en' => 'Correct:'],
            'vocabulary.wrong_count' => ['vi' => 'Sai:', 'en' => 'Wrong:'],
            'vocabulary.review_again' => ['vi' => 'Ôn Lại', 'en' => 'Review Again'],
            
            // Pronunciation recording
            'vocabulary.record_pronunciation' => ['vi' => 'Ghi Âm Phát Âm', 'en' => 'Record Pronunciation'],
            'vocabulary.recording' => ['vi' => 'Đang ghi âm... (3s)', 'en' => 'Recording... (3s)'],
            
            // Pronunciation feedback
            'vocabulary.checking_pronunciation' => ['vi' => 'Đang kiểm tra phát âm...', 'en' => 'Checking pronunciation...'],
            'vocabulary.pronunciation_excellent' => ['vi' => 'Xuất sắc! Điểm: {score}/100', 'en' => 'Excellent! Score: {score}/100'],
            'vocabulary.pronunciation_needs_improvement' => ['vi' => 'Cần cải thiện. Điểm: {score}/100', 'en' => 'Needs improvement. Score: {score}/100'],
            'vocabulary.pronunciation_error' => ['vi' => 'Lỗi', 'en' => 'Error'],
            
            // Pronunciation scores
            'vocabulary.accuracy' => ['vi' => 'Độ chính xác', 'en' => 'Accuracy'],
            'vocabulary.fluency' => ['vi' => 'Độ trôi chảy', 'en' => 'Fluency'],
            'vocabulary.completeness' => ['vi' => 'Độ hoàn thiện', 'en' => 'Completeness'],
            
            // Pronunciation comparison
            'vocabulary.you_said' => ['vi' => 'Bạn đã nói:', 'en' => 'You said:'],
            'vocabulary.should_pronounce' => ['vi' => 'Cần phát âm:', 'en' => 'Should pronounce:  
'],
            'vocabulary.not_recognized' => ['vi' => '(không nhận diện được)', 'en' => '(not recognized)'],
            
            // Phoneme errors
            'vocabulary.pronunciation_errors' => ['vi' => 'lỗi phát âm:', 'en' => 'pronunciation errors:'],
            'vocabulary.expected' => ['vi' => 'Mong đợi', 'en' => 'Expected'],
            'vocabulary.actual' => ['vi' => 'Phát âm', 'en' => 'Actual'],
            'vocabulary.perfect_pronunciation' => ['vi' => 'Phát âm hoàn hảo!', 'en' => 'Perfect pronunciation!'],
            'vocabulary.feedback_label' => ['vi' => 'Nhận xét:', 'en' => 'Feedback:'],
            
            // Error messages
            'vocabulary.error_failed_to_load' => ['vi' => 'Không thể tải danh sách từ vựng', 'en' => 'Failed to load vocabulary entries'],
            'vocabulary.error_failed_to_save' => ['vi' => 'Không thể lưu từ vựng', 'en' => 'Failed to save entry'],
            'vocabulary.error_failed_to_delete' => ['vi' => 'Không thể xóa từ vựng', 'en' => 'Failed to delete entry'],
            'vocabulary.error_no_words_yet' => ['vi' => 'Chưa có từ nào', 'en' => 'No words yet'],
            'vocabulary.error_add_words_first' => ['vi' => 'Thêm từ vào sổ từ vựng trước!', 'en' => 'Add some words to your vocabulary book first!'],
            'vocabulary.error_microphone_access' => ['vi' => 'Không thể truy cập microphone', 'en' => 'Could not access microphone'],
            'vocabulary.error_failed_upload' => ['vi' => 'Lỗi: {message}', 'en' => 'Error: {message}'],
            
            // Success messages
            'vocabulary.created_successfully' => ['vi' => 'Đã tạo từ vựng thành công', 'en' => 'Vocabulary entry created successfully'],
            'vocabulary.updated_successfully' => ['vi' => 'Đã cập nhật từ vựng thành công', 'en' => 'Vocabulary entry updated successfully'],
            'vocabulary.recorded_successfully' => ['vi' => 'Đã ghi âm (không thể kiểm tra phát âm)', 'en' => 'Recorded (pronunciation check unavailable)'],
            
            // Permissions
            'vocabulary.unauthorized' => ['vi' => 'Bạn không có quyền thực hiện hành động này', 'en' => 'You are not authorized to perform this action'],
            
            // Common actions
            'common.error' => ['vi' => 'Lỗi', 'en' => 'Error'],
            'common.success' => ['vi' => 'Thành công', 'en' => 'Success'],
            'common.info' => ['vi' => 'Thông tin', 'en' => 'Info'],
        ];

        foreach ($translations as $key => $values) {
            $parts = explode('.', $key, 2);
            $group = $parts[0];
            $keyName = $parts[1];

            // Vietnamese
            DB::table('translations')->updateOrInsert(
                ['language_id' => $viLang->id, 'group' => $group, 'key' => $keyName],
                ['value' => $values['vi'], 'updated_at' => now(), 'created_at' => now()]
            );

            // English
            DB::table('translations')->updateOrInsert(
                ['language_id' => $enLang->id, 'group' => $group, 'key' => $keyName],
                ['value' => $values['en'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->command->info('✅ Vocabulary translations seeded successfully!');
        $this->command->info('   Total translations: ' . count($translations));
    }
}
