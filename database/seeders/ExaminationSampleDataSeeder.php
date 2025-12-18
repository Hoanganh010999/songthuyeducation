<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExaminationSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates sample IELTS and Cambridge tests with full content
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get first user or use null
        $firstUser = DB::table('users')->orderBy('id')->first();
        $userId = $firstUser ? $firstUser->id : null;
        
        // Get first branch or use null
        $firstBranch = DB::table('branches')->orderBy('id')->first();
        $branchId = $firstBranch ? $firstBranch->id : null;

        // Clear existing sample data
        echo "🧹 Cleaning existing examination sample data...\n";
        DB::table('test_questions')->whereIn('test_id', function($query) {
            $query->select('id')->from('tests')->whereIn('title', [
                'IELTS Academic Practice Test 1',
                'Cambridge First (FCE) Practice Test'
            ]);
        })->delete();
        
        DB::table('test_sections')->whereIn('test_id', function($query) {
            $query->select('id')->from('tests')->whereIn('title', [
                'IELTS Academic Practice Test 1',
                'Cambridge First (FCE) Practice Test'
            ]);
        })->delete();
        
        DB::table('tests')->whereIn('title', [
            'IELTS Academic Practice Test 1',
            'Cambridge First (FCE) Practice Test'
        ])->delete();
        
        DB::table('questions')->whereIn('category_id', function($query) {
            $query->select('id')->from('question_categories')->whereIn('slug', [
                'ielts-practice', 'cambridge-english'
            ]);
        })->delete();
        
        DB::table('question_categories')->whereIn('slug', ['ielts-practice', 'cambridge-english'])->delete();
        DB::table('audio_tracks')->where('title', 'LIKE', '%IELTS Listening Practice%')->delete();
        DB::table('reading_passages')->where('title', 'LIKE', '%Coffee%')->delete();
        
        echo "✅ Cleanup completed\n\n";

        // ============================================
        // 1. CREATE AUDIO TRACKS
        // ============================================
        $audioTrackId = DB::table('audio_tracks')->insertGetId([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Listening Practice - Library Registration Conversation',
            'file_path' => 'audio/ielts_listening_section1.mp3',
            'file_url' => 'https://drive.google.com/file/d/1jChq25hjXUGhm-eayz9xNTlq1_EDwcmi/view?usp=sharing',
            'duration' => 300, // 5 minutes
            'transcript' => 'Librarian: Good morning. How can I help you today?
Student: Hi, I\'d like to register for a library card, please.
Librarian: Of course! Let me get some details from you. Can I have your full name?
Student: Yes, it\'s John Smith.
Librarian: Thank you. And what\'s your date of birth?
Student: I was born on the 15th of March, 2002.
Librarian: Great. And what\'s your current address?
Student: I live at 24 High Street, just around the corner.
Librarian: Perfect. And finally, I\'ll need a contact phone number.
Student: Sure, it\'s 07700 900123.
Librarian: Excellent. Now let me tell you about our services...',
            'timestamps' => json_encode([
                ['time' => '00:00', 'label' => 'Introduction'],
                ['time' => '00:30', 'label' => 'Personal Details'],
                ['time' => '02:00', 'label' => 'Library Services'],
                ['time' => '04:00', 'label' => 'Closing'],
            ]),
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ============================================
        // 2. CREATE READING PASSAGES
        // ============================================
        $readingPassageId = DB::table('reading_passages')->insertGetId([
            'uuid' => Str::uuid(),
            'title' => 'The History of Coffee',
            'content' => '<h3>The History of Coffee</h3>
<p>Coffee is one of the world\'s most beloved beverages, consumed by millions of people daily. The history of coffee begins in Ethiopia, where legend tells us that a goat herder named Kaldi discovered the energizing properties of coffee beans after noticing his goats became unusually energetic after eating berries from a certain tree.</p>

<p>From Ethiopia, coffee cultivation spread to the Arabian Peninsula. By the 15th century, coffee was being grown in Yemen, and by the 16th century, it had spread throughout Persia, Egypt, Syria, and Turkey. Coffee houses, known as "qahveh khaneh," began to appear in cities across the Middle East. These establishments became important centers for social activity and communication.</p>

<p>Coffee reached Europe in the 17th century, initially meeting with suspicion and controversy. Some called it "the bitter invention of Satan." However, after Pope Clement VIII tasted and approved of coffee, it gained rapid acceptance. Coffee houses began to spring up in major European cities, particularly in England, Austria, France, Germany, and Holland.</p>

<p>The coffee plant made its way to the Americas in the early 18th century, brought by a French naval officer to the Caribbean island of Martinique. From there, coffee cultivation spread throughout Central and South America. Today, Brazil is the world\'s largest coffee producer, followed by Vietnam, Colombia, and Indonesia.</p>

<p>The modern coffee industry has evolved significantly, with specialty coffee shops and sophisticated brewing methods becoming increasingly popular. Fair trade and sustainable coffee production have also become important considerations for many consumers, reflecting growing awareness of environmental and social issues in coffee-growing regions.</p>',
            'word_count' => 268,
            'source' => 'IELTS Reading Practice',
            'difficulty' => 'medium',
            'tags' => json_encode(['History', 'Culture', 'IELTS', 'Academic']),
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ============================================
        // 3. CREATE QUESTION CATEGORIES
        // ============================================
        $categoryIELTS = DB::table('question_categories')->insertGetId([
            'name' => 'IELTS Practice',
            'slug' => 'ielts-practice',
            'description' => 'Questions for IELTS exam preparation',
            'parent_id' => null,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categoryCambridge = DB::table('question_categories')->insertGetId([
            'name' => 'Cambridge English',
            'slug' => 'cambridge-english',
            'description' => 'Questions for Cambridge English exams (KET, PET, FCE, CAE, CPE)',
            'parent_id' => null,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ============================================
        // 4. CREATE IELTS TEST
        // ============================================
        $ieltsTestId = DB::table('tests')->insertGetId([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Academic Practice Test 1',
            'description' => 'Full-length IELTS Academic practice test covering all four skills: Listening, Reading, Writing, and Speaking',
            'instructions' => '<h3>Important Instructions</h3>
<ul>
<li>This test consists of 4 sections: Listening (30 min), Reading (60 min), Writing (60 min), Speaking (11-14 min)</li>
<li>You must complete all sections within the time limit</li>
<li>Do not open this test until you are ready to begin</li>
<li>Answer all questions</li>
<li>You may use a pencil and paper for notes</li>
<li>Read all instructions carefully</li>
</ul>',
            'type' => 'ielts',
            'subtype' => 'academic',
            'time_limit' => 185, // 3 hours 5 minutes total
            'total_points' => 9.0,
            'max_attempts' => 3,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'show_results' => 'after_submit',
            'show_answers' => true,
            'show_explanation' => true,
            'allow_review' => true,
            'require_camera' => false,
            'prevent_copy' => true,
            'prevent_tab_switch' => true,
            'available_from' => $now,
            'available_until' => $now->copy()->addMonths(6),
            'tags' => json_encode(['IELTS', 'Academic', 'Full Test', 'Practice']),
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ============================================
        // 5. CREATE IELTS LISTENING SECTION
        // ============================================
        $listeningSection = DB::table('test_sections')->insertGetId([
            'test_id' => $ieltsTestId,
            'title' => 'Listening',
            'description' => 'IELTS Listening Test - 4 sections, 40 questions, 30 minutes',
            'instructions' => '<p>You will hear four recordings and answer 40 questions. You will have time to read the questions before you listen. You will hear each recording once only. At the end of the test, you will have 10 minutes to transfer your answers to the answer sheet.</p>',
            'skill' => 'listening',
            'time_limit' => 30,
            'total_points' => 40,
            'sort_order' => 1,
            'audio_track_id' => $audioTrackId,
            'media' => json_encode([
                'audio_url' => 'https://drive.google.com/file/d/1jChq25hjXUGhm-eayz9xNTlq1_EDwcmi/view?usp=sharing',
                'allow_replay' => false,
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Listening Questions
        $this->createListeningQuestions($ieltsTestId, $listeningSection, $categoryIELTS, $audioTrackId, $userId, $branchId, $now);

        // ============================================
        // 6. CREATE IELTS READING SECTION
        // ============================================
        $readingSection = DB::table('test_sections')->insertGetId([
            'test_id' => $ieltsTestId,
            'title' => 'Reading',
            'description' => 'IELTS Reading Test - 3 passages, 40 questions, 60 minutes',
            'instructions' => '<p>Read the passages and answer the questions. You have 60 minutes to complete all 40 questions. Transfer your answers to the answer sheet as you go.</p>',
            'skill' => 'reading',
            'time_limit' => 60,
            'total_points' => 40,
            'sort_order' => 2,
            'passage_id' => $readingPassageId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Reading Questions
        $this->createReadingQuestions($ieltsTestId, $readingSection, $categoryIELTS, $readingPassageId, $userId, $branchId, $now);

        // ============================================
        // 7. CREATE IELTS WRITING SECTION
        // ============================================
        $writingSection = DB::table('test_sections')->insertGetId([
            'test_id' => $ieltsTestId,
            'title' => 'Writing',
            'description' => 'IELTS Writing Test - 2 tasks, 60 minutes',
            'instructions' => '<p>Task 1: Write at least 150 words describing visual information (20 minutes recommended). Task 2: Write at least 250 words in response to an opinion, argument or problem (40 minutes recommended).</p>',
            'skill' => 'writing',
            'time_limit' => 60,
            'total_points' => 9,
            'sort_order' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Writing Questions
        $this->createWritingQuestions($ieltsTestId, $writingSection, $categoryIELTS, $userId, $branchId, $now);

        // ============================================
        // 8. CREATE IELTS SPEAKING SECTION
        // ============================================
        $speakingSection = DB::table('test_sections')->insertGetId([
            'test_id' => $ieltsTestId,
            'title' => 'Speaking',
            'description' => 'IELTS Speaking Test - 3 parts, 11-14 minutes',
            'instructions' => '<p>Part 1: Introduction and interview (4-5 min). Part 2: Long turn - 2 minute speech (3-4 min including 1 min preparation). Part 3: Discussion (4-5 min).</p>',
            'skill' => 'speaking',
            'time_limit' => 14,
            'total_points' => 9,
            'sort_order' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Speaking Questions
        $this->createSpeakingQuestions($ieltsTestId, $speakingSection, $categoryIELTS, $userId, $branchId, $now);

        // ============================================
        // 9. CREATE CAMBRIDGE FCE TEST
        // ============================================
        $cambridgeTestId = DB::table('tests')->insertGetId([
            'uuid' => Str::uuid(),
            'title' => 'Cambridge First (FCE) Practice Test',
            'description' => 'Complete Cambridge First Certificate in English practice test',
            'instructions' => '<h3>Cambridge First (FCE) Examination</h3>
<p>This test consists of four papers:</p>
<ul>
<li>Reading and Use of English (75 minutes)</li>
<li>Writing (80 minutes)</li>
<li>Listening (about 40 minutes)</li>
<li>Speaking (14 minutes)</li>
</ul>',
            'type' => 'cambridge',
            'subtype' => 'fce',
            'time_limit' => 209, // Total time
            'total_points' => 100,
            'max_attempts' => 3,
            'shuffle_questions' => false,
            'show_results' => 'after_submit',
            'show_answers' => true,
            'allow_review' => true,
            'tags' => json_encode(['Cambridge', 'FCE', 'B2', 'Practice']),
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Cambridge FCE sections and questions
        $this->createCambridgeFCESections($cambridgeTestId, $categoryCambridge, $audioTrackId, $readingPassageId, $userId, $branchId, $now);

        echo "\n✅ Sample examination data created successfully!\n";
        echo "   📝 Created 2 full tests:\n";
        echo "      - IELTS Academic Practice Test (4 sections, ~80 questions)\n";
        echo "      - Cambridge First (FCE) Practice Test (4 papers, ~70 questions)\n";
        echo "   🎧 Audio track: https://drive.google.com/file/d/1jChq25hjXUGhm-eayz9xNTlq1_EDwcmi/view?usp=sharing\n\n";
    }

    private function createListeningQuestions($testId, $sectionId, $categoryId, $audioTrackId, $userId, $branchId, $now)
    {
        // Question 1-4: Form Completion
        $questions = [];
        
        // Form completion questions
        for ($i = 1; $i <= 4; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'category_id' => $categoryId,
                'skill' => 'listening',
                'difficulty' => 'easy',
                'type' => 'fill_blanks',
                'title' => "Complete the form below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.",
                'content' => json_encode([
                    'context' => 'Library Registration Form',
                    'form_fields' => [
                        ['label' => 'Name', 'blank' => $i == 1],
                        ['label' => 'Date of Birth', 'blank' => $i == 2],
                        ['label' => 'Address', 'blank' => $i == 3],
                        ['label' => 'Phone Number', 'blank' => $i == 4],
                    ],
                    'field_number' => $i,
                ]),
                'correct_answer' => json_encode([
                    1 => 'John Smith',
                    2 => '15 March',
                    3 => '24 High Street',
                    4 => '07700 900123',
                ][$i]),
                'explanation' => 'Listen carefully to the conversation between the student and librarian.',
                'audio_track_id' => $audioTrackId,
                'points' => 1,
                'branch_id' => $branchId,
                'created_by' => $userId,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('test_questions')->insert([
                'test_id' => $testId,
                'section_id' => $sectionId,
                'question_id' => $questionId,
                'sort_order' => $i,
                'points' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Question 5-10: Multiple Choice
        for ($i = 5; $i <= 10; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'category_id' => $categoryId,
                'skill' => 'listening',
                'difficulty' => 'medium',
                'type' => 'multiple_choice',
                'title' => "Choose the correct letter, A, B, or C.",
                'content' => json_encode([
                    'question' => [
                        5 => 'What time does the library close on weekdays?',
                        6 => 'How many books can students borrow at one time?',
                        7 => 'What is the fine for late returns?',
                        8 => 'Where is the computer room located?',
                        9 => 'What service is available on the ground floor?',
                        10 => 'When can students book study rooms?',
                    ][$i],
                    'options' => [
                        5 => ['A' => '8 PM', 'B' => '9 PM', 'C' => '10 PM'],
                        6 => ['A' => '3 books', 'B' => '5 books', 'C' => '10 books'],
                        7 => ['A' => '50p per day', 'B' => '£1 per day', 'C' => '£2 per day'],
                        8 => ['A' => 'Ground floor', 'B' => 'First floor', 'C' => 'Second floor'],
                        9 => ['A' => 'Printing service', 'B' => 'Cafe', 'C' => 'Both A and B'],
                        10 => ['A' => 'Online only', 'B' => 'At reception', 'C' => 'Both A and B'],
                    ][$i],
                ]),
                'correct_answer' => json_encode([5 => 'B', 6 => 'C', 7 => 'A', 8 => 'C', 9 => 'C', 10 => 'C'][$i]),
                'explanation' => 'Pay attention to specific details mentioned in the conversation.',
                'audio_track_id' => $audioTrackId,
                'points' => 1,
                'branch_id' => $branchId,
                'created_by' => $userId,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('test_questions')->insert([
                'test_id' => $testId,
                'section_id' => $sectionId,
                'question_id' => $questionId,
                'sort_order' => $i,
                'points' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function createReadingQuestions($testId, $sectionId, $categoryId, $passageId, $userId, $branchId, $now)
    {
        // Question 1-5: True/False/Not Given
        for ($i = 1; $i <= 5; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'category_id' => $categoryId,
                'skill' => 'reading',
                'difficulty' => 'medium',
                'type' => 'true_false_ng',
                'title' => "Do the following statements agree with the information in the reading passage? Write TRUE, FALSE, or NOT GIVEN.",
                'content' => json_encode([
                    'statement' => [
                        1 => 'Coffee was first discovered in Ethiopia.',
                        2 => 'Coffee houses in the Middle East were only for drinking coffee.',
                        3 => 'Pope Clement VIII initially rejected coffee.',
                        4 => 'Brazil is currently the largest coffee producer in the world.',
                        5 => 'All coffee consumers are concerned about fair trade.',
                    ][$i],
                ]),
                'correct_answer' => json_encode([1 => 'TRUE', 2 => 'FALSE', 3 => 'FALSE', 4 => 'TRUE', 5 => 'NOT GIVEN'][$i]),
                'explanation' => 'Check the passage carefully for explicit information.',
                'passage_id' => $passageId,
                'points' => 1,
                'branch_id' => $branchId,
                'created_by' => $userId,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('test_questions')->insert([
                'test_id' => $testId,
                'section_id' => $sectionId,
                'question_id' => $questionId,
                'sort_order' => $i,
                'points' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Question 6-10: Sentence Completion
        for ($i = 6; $i <= 10; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'category_id' => $categoryId,
                'skill' => 'reading',
                'difficulty' => 'medium',
                'type' => 'sentence_completion',
                'title' => "Complete the sentences below. Write NO MORE THAN THREE WORDS from the passage for each answer.",
                'content' => json_encode([
                    'sentence' => [
                        6 => 'A goat herder named ______ discovered coffee in Ethiopia.',
                        7 => 'By the 15th century, coffee was being grown in ______.',
                        8 => 'Coffee houses in the Middle East were called ______.',
                        9 => 'Coffee reached the Americas in the ______ century.',
                        10 => 'The modern industry focuses on ______ and sustainable production.',
                    ][$i],
                ]),
                'correct_answer' => json_encode([
                    6 => 'Kaldi',
                    7 => 'Yemen',
                    8 => 'qahveh khaneh',
                    9 => '18th / eighteenth',
                    10 => 'fair trade',
                ][$i]),
                'passage_id' => $passageId,
                'points' => 1,
                'branch_id' => $branchId,
                'created_by' => $userId,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('test_questions')->insert([
                'test_id' => $testId,
                'section_id' => $sectionId,
                'question_id' => $questionId,
                'sort_order' => $i,
                'points' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function createWritingQuestions($testId, $sectionId, $categoryId, $userId, $branchId, $now)
    {
        // Task 1: Report writing
        $task1Id = DB::table('questions')->insertGetId([
            'uuid' => Str::uuid(),
            'category_id' => $categoryId,
            'skill' => 'writing',
            'difficulty' => 'medium',
            'type' => 'essay',
            'title' => "IELTS Writing Task 1",
            'content' => json_encode([
                'task_type' => 'graph_description',
                'instructions' => 'The chart below shows the percentage of households in different age groups using the internet in the UK between 1998 and 2020. Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
                'image_url' => '/images/sample-chart.png',
                'min_words' => 150,
                'recommended_time' => 20,
            ]),
            'explanation' => 'Task 1 requires you to describe visual information objectively without giving opinions.',
            'points' => 9,
            'time_limit' => 1200, // 20 minutes
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('test_questions')->insert([
            'test_id' => $testId,
            'section_id' => $sectionId,
            'question_id' => $task1Id,
            'sort_order' => 1,
            'points' => 9,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Task 2: Essay
        $task2Id = DB::table('questions')->insertGetId([
            'uuid' => Str::uuid(),
            'category_id' => $categoryId,
            'skill' => 'writing',
            'difficulty' => 'hard',
            'type' => 'essay',
            'title' => "IELTS Writing Task 2",
            'content' => json_encode([
                'task_type' => 'opinion_essay',
                'prompt' => 'Some people believe that unpaid community service should be a compulsory part of high school programs (for example, working for a charity, improving the neighborhood, or teaching sports to younger children). To what extent do you agree or disagree?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
                'min_words' => 250,
                'recommended_time' => 40,
            ]),
            'explanation' => 'Task 2 requires you to present and justify an opinion with relevant examples and clear organization.',
            'points' => 9,
            'time_limit' => 2400, // 40 minutes
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('test_questions')->insert([
            'test_id' => $testId,
            'section_id' => $sectionId,
            'question_id' => $task2Id,
            'sort_order' => 2,
            'points' => 9,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createSpeakingQuestions($testId, $sectionId, $categoryId, $userId, $branchId, $now)
    {
        // Part 1: Introduction
        $part1Id = DB::table('questions')->insertGetId([
            'uuid' => Str::uuid(),
            'category_id' => $categoryId,
            'skill' => 'speaking',
            'difficulty' => 'easy',
            'type' => 'audio_response',
            'title' => "IELTS Speaking Part 1 - Introduction and Interview",
            'content' => json_encode([
                'part' => 1,
                'topics' => ['Home/Accommodation', 'Work/Study', 'Hobbies', 'Daily Routine'],
                'questions' => [
                    'Do you work or are you a student?',
                    'What do you like about your job/studies?',
                    'Do you have any hobbies?',
                    'What do you usually do in your free time?',
                    'Do you prefer to spend time alone or with others?',
                ],
                'time_limit' => 300, // 5 minutes
                'instructions' => 'Answer the examiner\'s questions about yourself and familiar topics.',
            ]),
            'points' => 9,
            'time_limit' => 300,
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Part 2: Long Turn
        $part2Id = DB::table('questions')->insertGetId([
            'uuid' => Str::uuid(),
            'category_id' => $categoryId,
            'skill' => 'speaking',
            'difficulty' => 'medium',
            'type' => 'audio_response',
            'title' => "IELTS Speaking Part 2 - Individual Long Turn",
            'content' => json_encode([
                'part' => 2,
                'topic_card' => 'Describe a book that you enjoyed reading.',
                'prompts' => [
                    'What the book was about',
                    'When you read it',
                    'Why you decided to read it',
                    'And explain why you enjoyed reading it',
                ],
                'preparation_time' => 60,
                'speaking_time' => 120,
                'instructions' => 'You will have 1 minute to prepare and make notes. Then speak for 1-2 minutes on the topic.',
            ]),
            'points' => 9,
            'time_limit' => 180,
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Part 3: Discussion
        $part3Id = DB::table('questions')->insertGetId([
            'uuid' => Str::uuid(),
            'category_id' => $categoryId,
            'skill' => 'speaking',
            'difficulty' => 'hard',
            'type' => 'audio_response',
            'title' => "IELTS Speaking Part 3 - Two-way Discussion",
            'content' => json_encode([
                'part' => 3,
                'topic' => 'Reading and Books',
                'questions' => [
                    'What kinds of books are most popular in your country?',
                    'Why do some people prefer reading books to watching TV?',
                    'Do you think e-books will replace paper books in the future?',
                    'What role do libraries play in modern society?',
                    'How has technology changed the way people read?',
                ],
                'time_limit' => 300,
                'instructions' => 'Discuss more abstract ideas and issues related to the Part 2 topic.',
            ]),
            'points' => 9,
            'time_limit' => 300,
            'branch_id' => $branchId,
            'created_by' => $userId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Link questions to section
        foreach ([$part1Id, $part2Id, $part3Id] as $order => $qId) {
            DB::table('test_questions')->insert([
                'test_id' => $testId,
                'section_id' => $sectionId,
                'question_id' => $qId,
                'sort_order' => $order + 1,
                'points' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function createCambridgeFCESections($cambridgeTestId, $categoryId, $audioTrackId, $passageId, $userId, $branchId, $now)
    {
        // Paper 1: Reading and Use of English
        $readingUseSection = DB::table('test_sections')->insertGetId([
            'test_id' => $cambridgeTestId,
            'title' => 'Reading and Use of English',
            'description' => 'Cambridge FCE - 7 parts, 52 questions, 75 minutes',
            'instructions' => '<p>This paper has 7 parts testing reading comprehension and use of English.</p>',
            'skill' => 'reading',
            'time_limit' => 75,
            'total_points' => 52,
            'sort_order' => 1,
            'passage_id' => $passageId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create some FCE questions
        for ($i = 1; $i <= 10; $i++) {
            $questionId = DB::table('questions')->insertGetId([
                'uuid' => Str::uuid(),
                'category_id' => $categoryId,
                'skill' => 'reading',
                'difficulty' => 'medium',
                'type' => 'multiple_choice',
                'title' => "Choose the correct answer A, B, C or D.",
                'content' => json_encode([
                    'question' => "Sample FCE Reading Question $i",
                    'options' => [
                        'A' => 'Option A',
                        'B' => 'Option B',
                        'C' => 'Option C',
                        'D' => 'Option D',
                    ],
                ]),
                'correct_answer' => json_encode(['A', 'B', 'C', 'D'][rand(0, 3)]),
                'passage_id' => $passageId,
                'points' => 1,
                'branch_id' => $branchId,
                'created_by' => $userId,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('test_questions')->insert([
                'test_id' => $cambridgeTestId,
                'section_id' => $readingUseSection,
                'question_id' => $questionId,
                'sort_order' => $i,
                'points' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Paper 2: Writing (2 tasks)
        $writingSection = DB::table('test_sections')->insertGetId([
            'test_id' => $cambridgeTestId,
            'title' => 'Writing',
            'description' => 'Cambridge FCE - 2 tasks, 80 minutes',
            'skill' => 'writing',
            'time_limit' => 80,
            'total_points' => 20,
            'sort_order' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Paper 3: Listening
        $listeningSection = DB::table('test_sections')->insertGetId([
            'test_id' => $cambridgeTestId,
            'title' => 'Listening',
            'description' => 'Cambridge FCE - 4 parts, 30 questions, 40 minutes',
            'skill' => 'listening',
            'time_limit' => 40,
            'total_points' => 30,
            'sort_order' => 3,
            'audio_track_id' => $audioTrackId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Paper 4: Speaking
        $speakingSection = DB::table('test_sections')->insertGetId([
            'test_id' => $cambridgeTestId,
            'title' => 'Speaking',
            'description' => 'Cambridge FCE - 4 parts, 14 minutes',
            'skill' => 'speaking',
            'time_limit' => 14,
            'total_points' => 15,
            'sort_order' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
