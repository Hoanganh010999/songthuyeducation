<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LessonPlan;
use App\Models\LessonPlanSession;
use App\Models\Subject;
use App\Models\Branch;

class IELTSSyllabusSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create English subject
        $subject = Subject::where('name', 'Tiếng Anh')->first();
        if (!$subject) {
            $subject = Subject::create([
                'name' => 'Tiếng Anh',
                'code' => 'ENG',
                'branch_id' => Branch::first()->id,
                'description' => 'English Language',
                'is_active' => true
            ]);
        }

        // Create IELTS 5.0 Syllabus
        $syllabus = LessonPlan::create([
            'branch_id' => Branch::first()->id,
            'subject_id' => $subject->id,
            'name' => 'IELTS 5.0',
            'code' => 'IELTS-5.0',
            'total_sessions' => 48,
            'level' => 'middle',
            'academic_year' => '2024-2025',
            'description' => 'Khóa học IELTS 5.0 - Toàn diện 4 kỹ năng Listening, Speaking, Reading, Writing',
            'created_by' => 1,
            'status' => 'in_use'
        ]);

        // Define 48 sessions
        $sessions = [
            // Week 1-2: Introduction & Listening Basics (4 sessions)
            ['title' => 'Giới thiệu khóa học & Chiến lược thi IELTS', 'objectives' => '<ul><li>Hiểu cấu trúc bài thi IELTS</li><li>Xác định mục tiêu học tập</li><li>Làm bài test đầu vào</li></ul>', 'content' => '<p><strong>IELTS Overview</strong></p><ul><li>Cấu trúc 4 kỹ năng</li><li>Thang điểm IELTS</li><li>Yêu cầu cho band 5.0</li></ul>'],
            ['title' => 'Listening Part 1: Personal Information', 'objectives' => '<ul><li>Nghe và ghi chú thông tin cá nhân</li><li>Nhận dạng số, tên, địa chỉ</li><li>Luyện tập spelling</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>Form completion</li><li>Names and addresses</li><li>Numbers and dates</li></ul>'],
            ['title' => 'Listening Part 2: Everyday Conversations', 'objectives' => '<ul><li>Hiểu hội thoại hàng ngày</li><li>Nắm từ vựng giao tiếp cơ bản</li><li>Multiple choice questions</li></ul>', 'content' => '<p><strong>Contexts:</strong></p><ul><li>Shopping</li><li>Making appointments</li><li>Asking for directions</li></ul>'],
            ['title' => 'Speaking Part 1: Introduction & Familiar Topics', 'objectives' => '<ul><li>Tự giới thiệu lưu loát</li><li>Trả lời câu hỏi về bản thân</li><li>Phát triển câu trả lời</li></ul>', 'content' => '<p><strong>Common Topics:</strong></p><ul><li>Hometown</li><li>Work/Study</li><li>Hobbies</li><li>Daily routine</li></ul>'],

            // Week 3-4: Reading Basics & Grammar (4 sessions)
            ['title' => 'Reading: Scanning & Skimming Techniques', 'objectives' => '<ul><li>Kỹ thuật đọc lướt</li><li>Tìm thông tin nhanh</li><li>True/False/Not Given</li></ul>', 'content' => '<p><strong>Skills:</strong></p><ul><li>Identifying keywords</li><li>Understanding main ideas</li><li>Time management</li></ul>'],
            ['title' => 'Grammar: Present Tenses', 'objectives' => '<ul><li>Present Simple</li><li>Present Continuous</li><li>Present Perfect</li></ul>', 'content' => '<p><strong>Usage & Practice:</strong></p><ul><li>Describing routines</li><li>Current situations</li><li>Life experiences</li></ul>'],
            ['title' => 'Writing Task 1: Introduction to Data Description', 'objectives' => '<ul><li>Cấu trúc bài Task 1</li><li>Mô tả biểu đồ cơ bản</li><li>Từ vựng miêu tả xu hướng</li></ul>', 'content' => '<p><strong>Types:</strong></p><ul><li>Line graphs</li><li>Bar charts</li><li>Pie charts</li><li>Tables</li></ul>'],
            ['title' => 'Vocabulary Building: Education & Work', 'objectives' => '<ul><li>50 từ vựng chủ đề giáo dục</li><li>50 từ vựng chủ đề công việc</li><li>Collocations thông dụng</li></ul>', 'content' => '<p><strong>Themes:</strong></p><ul><li>School subjects</li><li>Career paths</li><li>Workplace terms</li></ul>'],

            // Week 5-6: Listening & Speaking Development (4 sessions)
            ['title' => 'Listening Part 3: Academic Discussions', 'objectives' => '<ul><li>Nghe thảo luận học thuật</li><li>Note-taking strategies</li><li>Identifying speakers</li></ul>', 'content' => '<p><strong>Situations:</strong></p><ul><li>Study groups</li><li>Seminar discussions</li><li>Tutorial conversations</li></ul>'],
            ['title' => 'Speaking Part 2: Cue Card - Describing People', 'objectives' => '<ul><li>Cấu trúc mô tả người</li><li>Quản lý thời gian 2 phút</li><li>Từ vựng tính từ miêu tả</li></ul>', 'content' => '<p><strong>Sample Topics:</strong></p><ul><li>A person you admire</li><li>A friend from childhood</li><li>A family member</li></ul>'],
            ['title' => 'Pronunciation Practice: Word Stress & Intonation', 'objectives' => '<ul><li>Nhấn âm từ vựng</li><li>Ngữ điệu câu</li><li>Linking sounds</li></ul>', 'content' => '<p><strong>Focus:</strong></p><ul><li>Common word stress patterns</li><li>Question intonation</li><li>Sentence rhythm</li></ul>'],
            ['title' => 'Listening Part 4: Academic Lectures', 'objectives' => '<ul><li>Nghe bài giảng học thuật</li><li>Sentence completion</li><li>Summary completion</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>Science</li><li>History</li><li>Social studies</li></ul>'],

            // Week 7-8: Reading Strategies (4 sessions)
            ['title' => 'Reading: Matching Headings', 'objectives' => '<ul><li>Xác định ý chính đoạn văn</li><li>Matching headings strategies</li><li>Avoiding traps</li></ul>', 'content' => '<p><strong>Practice:</strong></p><ul><li>Paragraph analysis</li><li>Topic sentences</li><li>Supporting details</li></ul>'],
            ['title' => 'Reading: Multiple Choice Questions', 'objectives' => '<ul><li>Loại bỏ đáp án sai</li><li>Hiểu câu hỏi sâu</li><li>Paraphrasing recognition</li></ul>', 'content' => '<p><strong>Techniques:</strong></p><ul><li>Understanding question types</li><li>Eliminating distractors</li><li>Locating answers</li></ul>'],
            ['title' => 'Grammar: Past Tenses', 'objectives' => '<ul><li>Past Simple</li><li>Past Continuous</li><li>Past Perfect</li></ul>', 'content' => '<p><strong>Applications:</strong></p><ul><li>Telling stories</li><li>Describing experiences</li><li>Historical events</li></ul>'],
            ['title' => 'Vocabulary: Health & Environment', 'objectives' => '<ul><li>Medical terms</li><li>Environmental issues</li><li>Phrasal verbs</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>Healthcare system</li><li>Climate change</li><li>Pollution</li></ul>'],

            // Week 9-10: Writing Development (4 sessions)
            ['title' => 'Writing Task 2: Opinion Essays', 'objectives' => '<ul><li>Cấu trúc bài opinion</li><li>Thesis statement</li><li>Supporting arguments</li></ul>', 'content' => '<p><strong>Structure:</strong></p><ul><li>Introduction</li><li>Body paragraphs</li><li>Conclusion</li></ul>'],
            ['title' => 'Writing: Coherence & Cohesion', 'objectives' => '<ul><li>Linking words</li><li>Paragraph unity</li><li>Logical flow</li></ul>', 'content' => '<p><strong>Connectors:</strong></p><ul><li>Addition, contrast</li><li>Cause and effect</li><li>Exemplification</li></ul>'],
            ['title' => 'Writing Task 1: Process Diagrams', 'objectives' => '<ul><li>Mô tả quy trình</li><li>Sequencing language</li><li>Passive voice usage</li></ul>', 'content' => '<p><strong>Language:</strong></p><ul><li>First, then, next</li><li>After that, finally</li><li>The process shows</li></ul>'],
            ['title' => 'Grammar: Future Forms', 'objectives' => '<ul><li>Will vs Going to</li><li>Future Continuous</li><li>Future Perfect</li></ul>', 'content' => '<p><strong>Uses:</strong></p><ul><li>Predictions</li><li>Plans and intentions</li><li>Scheduled events</li></ul>'],

            // Week 11-12: Speaking Advanced (4 sessions)
            ['title' => 'Speaking Part 2: Describing Places', 'objectives' => '<ul><li>Mô tả địa điểm chi tiết</li><li>Sensory language</li><li>Past and present comparison</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>A city you visited</li><li>Your favorite place</li><li>A memorable location</li></ul>'],
            ['title' => 'Speaking Part 3: Discussion & Analysis', 'objectives' => '<ul><li>Thảo luận sâu chủ đề</li><li>Expressing opinions</li><li>Providing examples</li></ul>', 'content' => '<p><strong>Skills:</strong></p><ul><li>Agreeing/disagreeing</li><li>Speculation</li><li>Making comparisons</li></ul>'],
            ['title' => 'Fluency Practice: Extended Speaking', 'objectives' => '<ul><li>Giảm hesitation</li><li>Tăng tốc độ nói</li><li>Natural expressions</li></ul>', 'content' => '<p><strong>Activities:</strong></p><ul><li>Timed speaking</li><li>Topic discussions</li><li>Storytelling</li></ul>'],
            ['title' => 'Vocabulary: Technology & Media', 'objectives' => '<ul><li>IT terminology</li><li>Social media</li><li>Communication</li></ul>', 'content' => '<p><strong>Areas:</strong></p><ul><li>Digital devices</li><li>Internet terms</li><li>Modern communication</li></ul>'],

            // Week 13-14: Reading Advanced (4 sessions)
            ['title' => 'Reading: Summary Completion', 'objectives' => '<ul><li>Hoàn thành tóm tắt</li><li>Grammar awareness</li><li>Word form recognition</li></ul>', 'content' => '<p><strong>Focus:</strong></p><ul><li>Identifying keywords</li><li>Understanding context</li><li>Grammar accuracy</li></ul>'],
            ['title' => 'Reading: Matching Information', 'objectives' => '<ul><li>Matching thông tin cụ thể</li><li>Scanning techniques</li><li>Detail recognition</li></ul>', 'content' => '<p><strong>Strategies:</strong></p><ul><li>Keyword identification</li><li>Paragraph scanning</li><li>Time management</li></ul>'],
            ['title' => 'Grammar: Modal Verbs', 'objectives' => '<ul><li>Can, could, may, might</li><li>Should, must, have to</li><li>Expressing possibility</li></ul>', 'content' => '<p><strong>Functions:</strong></p><ul><li>Ability and permission</li><li>Obligation and advice</li><li>Deduction</li></ul>'],
            ['title' => 'Vocabulary: Culture & Society', 'objectives' => '<ul><li>Cultural terms</li><li>Social issues</li><li>Traditions</li></ul>', 'content' => '<p><strong>Themes:</strong></p><ul><li>Customs and festivals</li><li>Social problems</li><li>Community life</li></ul>'],

            // Week 15-16: Writing Advanced (4 sessions)
            ['title' => 'Writing Task 2: Discussion Essays', 'objectives' => '<ul><li>Discuss both views</li><li>Balanced arguments</li><li>Personal opinion</li></ul>', 'content' => '<p><strong>Structure:</strong></p><ul><li>Introduction with both views</li><li>Body: each view</li><li>Conclusion with opinion</li></ul>'],
            ['title' => 'Writing Task 2: Problem-Solution Essays', 'objectives' => '<ul><li>Identifying problems</li><li>Proposing solutions</li><li>Evaluating effectiveness</li></ul>', 'content' => '<p><strong>Format:</strong></p><ul><li>Problem introduction</li><li>Causes and effects</li><li>Solutions</li></ul>'],
            ['title' => 'Writing: Lexical Resource', 'objectives' => '<ul><li>Từ vựng học thuật</li><li>Paraphrasing</li><li>Avoiding repetition</li></ul>', 'content' => '<p><strong>Skills:</strong></p><ul><li>Synonyms usage</li><li>Collocations</li><li>Topic vocabulary</li></ul>'],
            ['title' => 'Writing Task 1: Maps & Diagrams', 'objectives' => '<ul><li>Describing changes</li><li>Comparing locations</li><li>Spatial language</li></ul>', 'content' => '<p><strong>Vocabulary:</strong></p><ul><li>Direction and position</li><li>Changes over time</li><li>Comparison language</li></ul>'],

            // Week 17-18: Integrated Skills (4 sessions)
            ['title' => 'Mock Test: Listening & Reading', 'objectives' => '<ul><li>Full Listening test</li><li>Full Reading test</li><li>Time management</li></ul>', 'content' => '<p><strong>Practice:</strong></p><ul><li>Academic conditions</li><li>Answer transfer</li><li>Review techniques</li></ul>'],
            ['title' => 'Mock Test Review & Error Analysis', 'objectives' => '<ul><li>Phân tích lỗi sai</li><li>Strategies improvement</li><li>Weak areas identification</li></ul>', 'content' => '<p><strong>Focus:</strong></p><ul><li>Common mistakes</li><li>Time allocation</li><li>Question types review</li></ul>'],
            ['title' => 'Grammar: Conditionals', 'objectives' => '<ul><li>Zero, First conditionals</li><li>Second, Third conditionals</li><li>Mixed conditionals</li></ul>', 'content' => '<p><strong>Usage:</strong></p><ul><li>Real situations</li><li>Hypothetical situations</li><li>Past hypotheticals</li></ul>'],
            ['title' => 'Vocabulary: Travel & Tourism', 'objectives' => '<ul><li>Travel vocabulary</li><li>Accommodation</li><li>Transportation</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>Booking and reservations</li><li>Tourist attractions</li><li>Travel problems</li></ul>'],

            // Week 19-20: Advanced Listening (4 sessions)
            ['title' => 'Listening: Note Completion Advanced', 'objectives' => '<ul><li>Complex note-taking</li><li>Abbreviated forms</li><li>Technical vocabulary</li></ul>', 'content' => '<p><strong>Skills:</strong></p><ul><li>Fast writing techniques</li><li>Understanding accents</li><li>Key information focus</li></ul>'],
            ['title' => 'Listening: Multiple Speakers', 'objectives' => '<ul><li>Phân biệt người nói</li><li>Opinions and attitudes</li><li>Agreement/disagreement</li></ul>', 'content' => '<p><strong>Practice:</strong></p><ul><li>Voice recognition</li><li>Understanding context</li><li>Following discussions</li></ul>'],
            ['title' => 'Speaking Part 2: Describing Events', 'objectives' => '<ul><li>Mô tả sự kiện</li><li>Chronological order</li><li>Emotional language</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>A memorable event</li><li>A celebration</li><li>An achievement</li></ul>'],
            ['title' => 'Grammar: Passive Voice', 'objectives' => '<ul><li>Passive formation</li><li>All tenses in passive</li><li>Academic writing usage</li></ul>', 'content' => '<p><strong>Applications:</strong></p><ul><li>Formal writing</li><li>Process description</li><li>Impersonal statements</li></ul>'],

            // Week 21-22: Advanced Reading (4 sessions)
            ['title' => 'Reading: Yes/No/Not Given', 'objectives' => '<ul><li>Phân biệt Yes/No/NG</li><li>Evidence identification</li><li>Inference skills</li></ul>', 'content' => '<p><strong>Techniques:</strong></p><ul><li>Careful reading</li><li>Avoiding assumptions</li><li>Text evidence</li></ul>'],
            ['title' => 'Reading: Sentence Completion', 'objectives' => '<ul><li>Grammar awareness</li><li>Word limit attention</li><li>Contextual understanding</li></ul>', 'content' => '<p><strong>Focus:</strong></p><ul><li>Grammatical fit</li><li>Meaning accuracy</li><li>Following instructions</li></ul>'],
            ['title' => 'Vocabulary: Science & Nature', 'objectives' => '<ul><li>Scientific terminology</li><li>Natural phenomena</li><li>Research vocabulary</li></ul>', 'content' => '<p><strong>Areas:</strong></p><ul><li>Biology, chemistry</li><li>Ecology</li><li>Research methods</li></ul>'],
            ['title' => 'Grammar: Reported Speech', 'objectives' => '<ul><li>Direct to indirect</li><li>Tense changes</li><li>Reporting verbs</li></ul>', 'content' => '<p><strong>Uses:</strong></p><ul><li>Statements, questions</li><li>Commands, requests</li><li>Time and place changes</li></ul>'],

            // Week 23-24: Writing Mastery (4 sessions)
            ['title' => 'Writing Task 2: Advantages-Disadvantages', 'objectives' => '<ul><li>Balanced essay</li><li>Weighing pros and cons</li><li>Clear conclusion</li></ul>', 'content' => '<p><strong>Structure:</strong></p><ul><li>Introduction</li><li>Advantages</li><li>Disadvantages</li><li>Conclusion</li></ul>'],
            ['title' => 'Writing: Grammatical Range & Accuracy', 'objectives' => '<ul><li>Complex sentences</li><li>Error-free writing</li><li>Variety in structures</li></ul>', 'content' => '<p><strong>Grammar:</strong></p><ul><li>Relative clauses</li><li>Participle clauses</li><li>Inversions</li></ul>'],
            ['title' => 'Writing: Task Achievement', 'objectives' => '<ul><li>Answering all parts</li><li>Relevant examples</li><li>Adequate development</li></ul>', 'content' => '<p><strong>Requirements:</strong></p><ul><li>Clear position</li><li>Supporting ideas</li><li>Logical conclusion</li></ul>'],
            ['title' => 'Mock Test: Writing Tasks 1 & 2', 'objectives' => '<ul><li>Full Writing test</li><li>60 minutes practice</li><li>Under exam conditions</li></ul>', 'content' => '<p><strong>Tasks:</strong></p><ul><li>Task 1: 20 minutes</li><li>Task 2: 40 minutes</li><li>Word count check</li></ul>'],

            // Week 25-26: Speaking Mastery (4 sessions)
            ['title' => 'Speaking Part 2: Abstract Topics', 'objectives' => '<ul><li>Khái niệm trừu tượng</li><li>Developing complex ideas</li><li>Philosophical thinking</li></ul>', 'content' => '<p><strong>Topics:</strong></p><ul><li>Success and failure</li><li>Happiness</li><li>Change and tradition</li></ul>'],
            ['title' => 'Speaking Part 3: Contemporary Issues', 'objectives' => '<ul><li>Current affairs</li><li>Critical thinking</li><li>Complex argumentation</li></ul>', 'content' => '<p><strong>Themes:</strong></p><ul><li>Globalization</li><li>Technology impact</li><li>Social changes</li></ul>'],
            ['title' => 'Pronunciation: Advanced Features', 'objectives' => '<ul><li>Connected speech</li><li>Weak forms</li><li>Sentence stress</li></ul>', 'content' => '<p><strong>Practice:</strong></p><ul><li>Natural speech patterns</li><li>Rhythm and pace</li><li>Clarity vs speed</li></ul>'],
            ['title' => 'Mock Test: Speaking Full Test', 'objectives' => '<ul><li>Complete speaking exam</li><li>11-14 minutes</li><li>Feedback session</li></ul>', 'content' => '<p><strong>Parts:</strong></p><ul><li>Part 1: 4-5 min</li><li>Part 2: 3-4 min</li><li>Part 3: 4-5 min</li></ul>'],
        ];

        // Create all sessions
        foreach ($sessions as $index => $session) {
            LessonPlanSession::create([
                'lesson_plan_id' => $syllabus->id,
                'session_number' => $index + 1,
                'lesson_title' => $session['title'],
                'lesson_objectives' => $session['objectives'],
                'lesson_content' => $session['content'],
                'duration_minutes' => 90,
                'notes' => 'Session ' . ($index + 1) . ' of IELTS 5.0 Course'
            ]);
        }

        $this->command->info('✓ IELTS 5.0 Syllabus with 48 sessions created successfully!');
    }
}
