<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Seeder tạo IELTS tests HOÀN CHỈNH với nội dung thực tế
 */
class IELTSComprehensiveTestsSeeder extends Seeder
{
    private $userId;
    private $branchId;
    private $now;

    public function run(): void
    {
        $this->now = Carbon::now();
        
        $firstUser = DB::table('users')->orderBy('id')->first();
        $this->userId = $firstUser ? $firstUser->id : null;
        
        $firstBranch = DB::table('branches')->orderBy('id')->first();
        $this->branchId = $firstBranch ? $firstBranch->id : null;

        // XÓA TẤT CẢ TESTS IELTS CŨ (rỗng)
        echo "🧹 Cleaning all IELTS tests...\n";
        DB::table('tests')->where('type', 'ielts')->delete();
        echo "✅ Cleanup completed\n\n";

        // TẠO TESTS LISTENING HOÀN CHỈNH
        $this->createListeningTests();
        
        // TẠO TESTS READING HOÀN CHỈNH
        $this->createReadingTests();
        
        // TẠO TESTS WRITING HOÀN CHỈNH
        $this->createWritingTests();

        echo "\n================================================\n";
        echo "✅ IELTS Comprehensive Tests Created!\n";
        echo "================================================\n";
        echo "📊 Summary:\n";
        echo "  Listening: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'listening')->count() . " tests\n";
        echo "  Reading: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'reading')->count() . " tests\n";
        echo "  Writing: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'writing')->count() . " tests\n";
        echo "================================================\n\n";
    }

    private function createListeningTests()
    {
        echo "📝 Creating IELTS Listening Tests with full content...\n";

        // Listening Test 1: Library Registration
        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Listening Practice Test 1 - Library Registration',
            'description' => 'Conversation about registering at a library',
            'instructions' => '<p>You will hear a conversation between a student and a librarian. Listen carefully and answer questions 1-10.</p>',
            'type' => 'ielts',
            'subtype' => 'listening',
            'time_limit' => 10,
            'passing_score' => 60.00,
            'total_points' => 10,
            'max_attempts' => 3,
            'settings' => json_encode([
                'audio_url' => 'https://drive.google.com/file/d/1jChq25hjXUGhm-eayz9xNTlq1_EDwcmi/view?usp=sharing',
                'transcript' => 'Librarian: Good morning, how can I help you?\nStudent: Hi, I\'d like to register for a library card.\nLibrarian: Of course. Can I have your full name?\nStudent: It\'s Sarah Johnson.\nLibrarian: Thank you. And your date of birth?\nStudent: 15th March, 2000.\nLibrarian: Great. What\'s your current address?\nStudent: 42 Park Avenue, Manchester.\nLibrarian: Perfect. And your phone number?\nStudent: It\'s 07700 900456.\nLibrarian: Excellent. Now, we offer three types of membership...',
                'questions' => [
                    ['number' => 1, 'type' => 'fill_blank', 'question' => 'Name: Sarah _____', 'answer' => 'Johnson'],
                    ['number' => 2, 'type' => 'fill_blank', 'question' => 'Date of birth: 15th _____, 2000', 'answer' => 'March'],
                    ['number' => 3, 'type' => 'fill_blank', 'question' => 'Address: 42 _____ Avenue', 'answer' => 'Park'],
                    ['number' => 4, 'type' => 'fill_blank', 'question' => 'City: _____', 'answer' => 'Manchester'],
                    ['number' => 5, 'type' => 'fill_blank', 'question' => 'Phone: 07700 _____', 'answer' => '900456'],
                    ['number' => 6, 'type' => 'mcq', 'question' => 'How many types of membership are offered?', 'options' => ['Two', 'Three', 'Four'], 'answer' => 'B'],
                    ['number' => 7, 'type' => 'mcq', 'question' => 'What is the standard membership fee?', 'options' => ['£15', '£20', '£25'], 'answer' => 'B'],
                    ['number' => 8, 'type' => 'mcq', 'question' => 'How many books can be borrowed at once?', 'options' => ['3', '5', '10'], 'answer' => 'C'],
                    ['number' => 9, 'type' => 'mcq', 'question' => 'What is the loan period?', 'options' => ['2 weeks', '3 weeks', '4 weeks'], 'answer' => 'B'],
                    ['number' => 10, 'type' => 'mcq', 'question' => 'Where is the computer room?', 'options' => ['Ground floor', 'First floor', 'Second floor'], 'answer' => 'C'],
                ]
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 1 complete Listening test\n";
    }

    private function createReadingTests()
    {
        echo "📝 Creating IELTS Reading Tests with full content...\n";

        // Reading Test 1: Climate Change
        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Reading Practice Test 1 - Climate Change',
            'description' => 'Reading passage about climate change and its impacts',
            'instructions' => '<p>Read the passage and answer questions 1-10. You have 20 minutes.</p>',
            'type' => 'ielts',
            'subtype' => 'reading',
            'time_limit' => 20,
            'passing_score' => 60.00,
            'total_points' => 10,
            'max_attempts' => 3,
            'settings' => json_encode([
                'passages' => [[
                    'title' => 'Climate Change and Its Global Impact',
                    'content' => '<h3>Climate Change and Its Global Impact</h3>

<p>Climate change represents one of the most significant challenges facing humanity in the 21st century. The phenomenon, primarily driven by human activities such as burning fossil fuels and deforestation, has led to a measurable increase in global temperatures. Scientific consensus indicates that the average global temperature has risen by approximately 1.1 degrees Celsius since the pre-industrial era, with most of this warming occurring in the past 40 years.</p>

<p>The consequences of this warming are far-reaching and multifaceted. Rising sea levels threaten coastal communities worldwide, with some island nations facing the prospect of complete submersion. The Arctic ice cap is melting at an unprecedented rate, reducing by approximately 13% per decade. This has severe implications for both wildlife, particularly polar bears and seals, and for global weather patterns.</p>

<p>Agricultural systems are also feeling the impact. Changes in precipitation patterns and increased frequency of extreme weather events such as droughts and floods are affecting crop yields. Some regions are experiencing water scarcity, while others face unexpected flooding. These changes threaten food security, particularly in developing nations that lack the resources to adapt quickly.</p>

<p>However, the response to climate change has been varied across the globe. The Paris Agreement of 2015 marked a significant milestone, with 196 countries committing to limit global warming to well below 2 degrees Celsius above pre-industrial levels. Many nations have set ambitious targets for reducing carbon emissions and transitioning to renewable energy sources. Solar and wind power have become increasingly cost-competitive with fossil fuels, making the transition economically feasible.</p>

<p>Despite progress, challenges remain. Developing economies argue that they should not be expected to limit their growth to the same extent as developed nations, given that industrialized countries are historically responsible for the majority of emissions. This debate over climate justice continues to complicate international negotiations.</p>',
                    'word_count' => 320,
                    'questions' => [
                        ['number' => 1, 'type' => 'true_false_ng', 'statement' => 'Global temperatures have increased by 1.1°C since the pre-industrial era.', 'answer' => 'TRUE'],
                        ['number' => 2, 'type' => 'true_false_ng', 'statement' => 'The Arctic ice cap is growing at 13% per decade.', 'answer' => 'FALSE'],
                        ['number' => 3, 'type' => 'true_false_ng', 'statement' => 'All countries have achieved their Paris Agreement targets.', 'answer' => 'NOT GIVEN'],
                        ['number' => 4, 'type' => 'true_false_ng', 'statement' => 'Solar power is now cost-competitive with fossil fuels.', 'answer' => 'TRUE'],
                        ['number' => 5, 'type' => 'true_false_ng', 'statement' => 'Developing nations are primarily responsible for historical emissions.', 'answer' => 'FALSE'],
                        ['number' => 6, 'type' => 'sentence_completion', 'sentence' => 'The Paris Agreement was signed by _____ countries.', 'answer' => '196'],
                        ['number' => 7, 'type' => 'sentence_completion', 'sentence' => 'Polar bears and _____ are affected by Arctic ice melting.', 'answer' => 'seals'],
                        ['number' => 8, 'type' => 'sentence_completion', 'sentence' => 'Changes in _____ patterns are affecting agriculture.', 'answer' => 'precipitation'],
                        ['number' => 9, 'type' => 'mcq', 'question' => 'What is the main cause of climate change?', 'options' => ['Natural disasters', 'Human activities', 'Solar radiation'], 'answer' => 'B'],
                        ['number' => 10, 'type' => 'mcq', 'question' => 'What is the debate about climate justice mainly concerned with?', 'options' => ['Technology transfer', 'Emission responsibilities', 'Economic growth'], 'answer' => 'B'],
                    ]
                ]]
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 1 complete Reading test\n";
    }

    private function createWritingTests()
    {
        echo "📝 Creating IELTS Writing Tests with full content...\n";

        // Writing Test 1: Task 1 + Task 2
        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Writing Practice Test 1 - Academic',
            'description' => 'Complete writing test with Task 1 (graph) and Task 2 (essay)',
            'instructions' => '<p>This test contains two writing tasks. You have 60 minutes in total.</p><p>Task 1: Spend about 20 minutes on this task. Write at least 150 words.</p><p>Task 2: Spend about 40 minutes on this task. Write at least 250 words.</p>',
            'type' => 'ielts',
            'subtype' => 'writing',
            'time_limit' => 60,
            'passing_score' => 60.00,
            'total_points' => 9,
            'max_attempts' => 3,
            'settings' => json_encode([
                'tasks' => [
                    [
                        'number' => 1,
                        'title' => 'Task 1 - Graph Description',
                        'prompt' => '<p>The chart below shows the percentage of households in owned and rented accommodation in England and Wales between 1918 and 2011.</p><p><strong>Summarize the information by selecting and reporting the main features, and make comparisons where relevant.</strong></p><p>Write at least 150 words.</p>',
                        'image_description' => 'Bar chart showing owned vs rented accommodation from 1918 to 2011. In 1918, about 77% rented and 23% owned. By 2011, about 35% rented and 65% owned, showing a significant shift over time.',
                        'min_words' => 150,
                        'time_limit' => 20,
                        'sample_answer' => 'The bar chart illustrates the proportion of households in England and Wales that were owned or rented between 1918 and 2011. Overall, there was a dramatic increase in home ownership over the period, while the percentage of rented accommodation decreased correspondingly...'
                    ],
                    [
                        'number' => 2,
                        'title' => 'Task 2 - Opinion Essay',
                        'prompt' => '<p><strong>Some people think that universities should provide graduates with the knowledge and skills needed in the workplace. Others think that the true function of a university should be to give access to knowledge for its own sake, regardless of whether the course is useful to an employer.</strong></p><p>What, in your opinion, should be the main function of a university?</p><p>Give reasons for your answer and include any relevant examples from your own knowledge or experience.</p><p>Write at least 250 words.</p>',
                        'min_words' => 250,
                        'time_limit' => 40,
                        'sample_answer' => 'The debate over the primary purpose of universities has been ongoing for decades. While some argue that higher education should focus on preparing students for employment, others believe universities should pursue knowledge for its own merits. In my view, universities should strike a balance between these two objectives...'
                    ]
                ]
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 1 complete Writing test\n";
    }
}

