<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Seeder tạo 2 đề IELTS đầy đủ cho mỗi skill (Listening, Reading, Writing)
 * - Listening: 2 đề x 4 parts x 10 câu = 80 câu total
 * - Reading: 2 đề x 3 passages x ~13 câu = 80 câu total  
 * - Writing: 2 đề x 2 tasks = 4 tasks total
 */
class IELTSFullSampleTestsSeeder extends Seeder
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

        echo "🧹 Cleaning existing IELTS tests...\n";
        DB::table('tests')->where('type', 'ielts')->delete();
        echo "✅ Cleanup completed\n\n";

        $this->createListeningTests();
        $this->createReadingTests();
        $this->createWritingTests();

        echo "\n================================================\n";
        echo "✅ IELTS Full Sample Tests Created!\n";
        echo "================================================\n";
        echo "📊 Summary:\n";
        echo "  Listening: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'listening')->count() . " tests (40 questions each)\n";
        echo "  Reading: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'reading')->count() . " tests (40 questions each)\n";
        echo "  Writing: " . DB::table('tests')->where('type', 'ielts')->where('subtype', 'writing')->count() . " tests (2 tasks each)\n";
        echo "================================================\n\n";
    }

    private function createListeningTests()
    {
        echo "📝 Creating 2 Full IELTS Listening Tests (4 parts each)...\n";

        // LISTENING TEST 1 - Social Situations
        $questions1 = [];
        // Part 1: Accommodation (Q1-10)
        for ($i = 1; $i <= 10; $i++) {
            $questions1[] = [
                'number' => $i,
                'content' => "Question $i about accommodation details",
                'type' => 'short_answer',
                'answer' => "Answer $i"
            ];
        }
        // Part 2: Campus Facilities (Q11-20)
        for ($i = 11; $i <= 20; $i++) {
            $questions1[] = [
                'number' => $i,
                'content' => "Question $i about campus facilities",
                'type' => 'multiple_choice',
                'options' => [
                    ['value' => 'A', 'text' => 'Option A'],
                    ['value' => 'B', 'text' => 'Option B'],
                    ['value' => 'C', 'text' => 'Option C']
                ],
                'answer' => 'B'
            ];
        }
        // Part 3: Academic Discussion (Q21-30)
        for ($i = 21; $i <= 30; $i++) {
            $questions1[] = [
                'number' => $i,
                'content' => "Question $i about academic discussion",
                'type' => 'multiple_choice',
                'options' => [
                    ['value' => 'A', 'text' => 'Option A'],
                    ['value' => 'B', 'text' => 'Option B'],
                    ['value' => 'C', 'text' => 'Option C']
                ],
                'answer' => 'A'
            ];
        }
        // Part 4: Lecture (Q31-40)
        for ($i = 31; $i <= 40; $i++) {
            $questions1[] = [
                'number' => $i,
                'content' => "Question $i: Complete the sentence",
                'type' => 'sentence_completion',
                'prefix' => "The lecture mentions that",
                'suffix' => "is an important factor.",
                'answer' => "Answer $i"
            ];
        }

        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Listening Practice Test 1',
            'description' => 'Full IELTS Listening test with 4 parts covering social and academic situations',
            'instructions' => '<p>You will hear a number of different recordings and you will have to answer questions on what you hear. There will be time for you to read the instructions and questions and you will have a chance to check your work. All the recordings will be played ONCE only.</p>',
            'type' => 'ielts',
            'subtype' => 'listening',
            'time_limit' => 30,
            'passing_score' => 50.00,
            'total_points' => 40,
            'max_attempts' => 3,
            'settings' => json_encode([
                'audio_url' => '/storage/examination/audio/listening-test-1.mp3',
                'parts' => [
                    [
                        'number' => 1,
                        'title' => 'Part 1: Accommodation Enquiry',
                        'description' => 'A conversation between two people in an everyday social context',
                        'audio_url' => '', // Could be separate audio per part
                        'questions_range' => '1-10'
                    ],
                    [
                        'number' => 2,
                        'title' => 'Part 2: Campus Tour',
                        'description' => 'A monologue set in an everyday social context',
                        'audio_url' => '',
                        'questions_range' => '11-20'
                    ],
                    [
                        'number' => 3,
                        'title' => 'Part 3: Study Project Discussion',
                        'description' => 'A conversation between up to four people in an educational context',
                        'audio_url' => '',
                        'questions_range' => '21-30'
                    ],
                    [
                        'number' => 4,
                        'title' => 'Part 4: Environmental Science Lecture',
                        'description' => 'A monologue on an academic subject',
                        'audio_url' => '',
                        'questions_range' => '31-40'
                    ]
                ],
                'questions' => $questions1
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // LISTENING TEST 2 - Academic Focus
        $questions2 = [];
        // Part 1: Library Registration (Q1-10)
        for ($i = 1; $i <= 10; $i++) {
            $questions2[] = [
                'number' => $i,
                'content' => "Library registration question $i",
                'type' => 'short_answer',
                'answer' => "Answer $i"
            ];
        }
        // Part 2: University Services (Q11-20)
        for ($i = 11; $i <= 20; $i++) {
            $questions2[] = [
                'number' => $i,
                'content' => "University services question $i",
                'type' => 'multiple_choice',
                'options' => [
                    ['value' => 'A', 'text' => 'Option A'],
                    ['value' => 'B', 'text' => 'Option B'],
                    ['value' => 'C', 'text' => 'Option C']
                ],
                'answer' => 'C'
            ];
        }
        // Part 3: Research Methods (Q21-30)
        for ($i = 21; $i <= 30; $i++) {
            $questions2[] = [
                'number' => $i,
                'content' => "Research methods question $i",
                'type' => 'multiple_choice',
                'options' => [
                    ['value' => 'A', 'text' => 'Option A'],
                    ['value' => 'B', 'text' => 'Option B'],
                    ['value' => 'C', 'text' => 'Option C']
                ],
                'answer' => 'B'
            ];
        }
        // Part 4: History Lecture (Q31-40)
        for ($i = 31; $i <= 40; $i++) {
            $questions2[] = [
                'number' => $i,
                'content' => "History lecture question $i",
                'type' => 'sentence_completion',
                'prefix' => "In the lecture about",
                'suffix' => "was discussed.",
                'answer' => "Answer $i"
            ];
        }

        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Listening Practice Test 2',
            'description' => 'Full IELTS Listening test with 4 parts covering academic and daily contexts',
            'instructions' => '<p>You will hear a number of different recordings and you will have to answer questions on what you hear. There will be time for you to read the instructions and questions and you will have a chance to check your work. All the recordings will be played ONCE only.</p>',
            'type' => 'ielts',
            'subtype' => 'listening',
            'time_limit' => 30,
            'passing_score' => 50.00,
            'total_points' => 40,
            'max_attempts' => 3,
            'settings' => json_encode([
                'audio_url' => '/storage/examination/audio/listening-test-2.mp3',
                'parts' => [
                    [
                        'number' => 1,
                        'title' => 'Part 1: Library Registration',
                        'description' => 'A conversation in an everyday context',
                        'questions_range' => '1-10'
                    ],
                    [
                        'number' => 2,
                        'title' => 'Part 2: University Services',
                        'description' => 'A monologue about university facilities',
                        'questions_range' => '11-20'
                    ],
                    [
                        'number' => 3,
                        'title' => 'Part 3: Research Methods Discussion',
                        'description' => 'A conversation in an academic context',
                        'questions_range' => '21-30'
                    ],
                    [
                        'number' => 4,
                        'title' => 'Part 4: History Lecture',
                        'description' => 'A lecture on an academic topic',
                        'questions_range' => '31-40'
                    ]
                ],
                'questions' => $questions2
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 2 complete Listening tests (40 questions each)\n";
    }

    private function createReadingTests()
    {
        echo "📝 Creating 2 Full IELTS Reading Tests (3 passages each)...\n";

        // READING TEST 1 - Academic Topics
        $passages1 = [
            [
                'number' => 1,
                'title' => 'The History of Timekeeping',
                'subtitle' => 'How humans learned to measure time',
                'content' => '<p><strong>A.</strong> Since ancient times, humans have had the need to measure time. The earliest timekeeping devices were sundials, which used the position of the sun to indicate the time of day. These were simple but effective for daytime use. However, they had obvious limitations when the sun was not visible.</p>

<p><strong>B.</strong> Water clocks, or clepsydras, were developed around 1500 BCE in Egypt and Mesopotamia. These devices measured time by the regulated flow of water from one container to another. They were more reliable than sundials as they could work at night and on cloudy days. The ancient Greeks and Romans refined these devices, creating increasingly accurate mechanisms.</p>

<p><strong>C.</strong> The mechanical clock emerged in Europe during the 13th century, representing a major breakthrough in timekeeping technology. These clocks used weights and gears to regulate time. By the 14th century, mechanical clocks had spread throughout Europe, and their accuracy continued to improve. The invention of the pendulum clock by Christiaan Huygens in 1656 marked another significant advancement.</p>

<p><strong>D.</strong> The 20th century saw revolutionary changes in timekeeping. Quartz clocks, developed in the 1920s, used the vibrations of quartz crystals to keep time with unprecedented accuracy. These were later miniaturized for use in wristwatches. The development of atomic clocks in the 1950s took accuracy to another level entirely, losing only one second every 100 million years.</p>

<p><strong>E.</strong> Today, atomic clocks are used to maintain international time standards. They are essential for GPS navigation, telecommunications, and scientific research. The quest for ever more accurate timekeeping continues, with optical lattice clocks promising even greater precision in the future.</p>',
                'questionStart' => 1,
                'questionEnd' => 13,
                'questions' => $this->generateReadingQuestions(1, 13)
            ],
            [
                'number' => 2,
                'title' => 'Urban Green Spaces',
                'subtitle' => 'The importance of parks in cities',
                'content' => '<p>Urban green spaces, including parks, gardens, and street trees, play a vital role in modern cities. Research has consistently shown that access to green spaces improves both physical and mental health. People living near parks are more likely to exercise regularly and report lower stress levels.</p>

<p>These spaces also provide important environmental benefits. Trees and plants help to reduce air pollution by absorbing carbon dioxide and releasing oxygen. They also help to cool urban areas, which can be significantly warmer than surrounding rural areas due to the heat island effect. During heavy rainfall, green spaces absorb water, reducing the risk of flooding.</p>

<p>Furthermore, urban green spaces support biodiversity by providing habitats for various species of birds, insects, and small mammals. This is particularly important as urbanization continues to encroach on natural habitats. Well-designed parks can serve as corridors, allowing wildlife to move between different areas.</p>

<p>From a social perspective, parks and green spaces serve as important community gathering points. They provide venues for recreation, social interaction, and cultural events. Studies have shown that neighborhoods with good access to green spaces have stronger social cohesion and lower crime rates.</p>',
                'questionStart' => 14,
                'questionEnd' => 27,
                'questions' => $this->generateReadingQuestions(14, 27)
            ],
            [
                'number' => 3,
                'title' => 'The Future of Work',
                'subtitle' => 'How technology is changing employment',
                'content' => '<p>The nature of work is undergoing profound changes driven by technological advances. Automation and artificial intelligence are transforming industries, with some jobs disappearing while new ones emerge. This shift requires workers to continuously update their skills to remain competitive in the job market.</p>

<p>Remote work has become increasingly common, accelerated by recent global events. Many companies have discovered that employees can be productive working from home, leading to more flexible work arrangements. This trend has implications for office space requirements, commuting patterns, and work-life balance.</p>

<p>The gig economy continues to grow, with more people working as freelancers or on short-term contracts rather than in traditional full-time employment. While this offers flexibility, it also raises concerns about job security and benefits. Policymakers are grappling with how to protect workers in this new employment landscape.</p>

<p>Looking ahead, experts predict that skills such as creativity, emotional intelligence, and complex problem-solving will become increasingly valuable. These are abilities that complement rather than compete with artificial intelligence. Educational institutions are beginning to adapt their curricula to prepare students for this evolving workplace.</p>',
                'questionStart' => 28,
                'questionEnd' => 40,
                'questions' => $this->generateReadingQuestions(28, 40)
            ]
        ];

        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Reading Practice Test 1',
            'description' => 'Full IELTS Academic Reading test with 3 passages covering diverse topics',
            'instructions' => '<p>You should spend about 60 minutes on this task. Read the three passages and answer all questions. Write your answers on the answer sheet.</p>',
            'type' => 'ielts',
            'subtype' => 'reading',
            'time_limit' => 60,
            'passing_score' => 50.00,
            'total_points' => 40,
            'max_attempts' => 3,
            'settings' => json_encode([
                'passages' => $passages1
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // READING TEST 2
        $passages2 = [
            [
                'number' => 1,
                'title' => 'The Impact of Social Media',
                'subtitle' => 'How online platforms are changing society',
                'content' => '<p>Social media platforms have fundamentally altered how people communicate and share information. With billions of users worldwide, these platforms have become integral to modern life, influencing everything from personal relationships to political movements.</p>

<p>The speed at which information spreads on social media is unprecedented. News and opinions can reach millions of people within hours, bypassing traditional media gatekeepers. This democratization of information has both positive and negative consequences. While it allows diverse voices to be heard, it also facilitates the spread of misinformation.</p>

<p>Young people are particularly affected by social media. Studies have shown correlations between heavy social media use and increased rates of anxiety and depression among teenagers. The constant exposure to curated versions of others\' lives can lead to unhealthy comparisons and unrealistic expectations.</p>

<p>However, social media also offers significant benefits. It enables people to maintain connections across distances, find communities with shared interests, and mobilize for social causes. Many businesses rely on social media for marketing and customer engagement. The challenge lies in maximizing these benefits while mitigating the negative effects.</p>',
                'questionStart' => 1,
                'questionEnd' => 13,
                'questions' => $this->generateReadingQuestions(1, 13)
            ],
            [
                'number' => 2,
                'title' => 'Renewable Energy Revolution',
                'subtitle' => 'The transition to clean power',
                'content' => '<p>The global energy sector is undergoing a massive transformation as countries shift from fossil fuels to renewable sources. Solar and wind power have become increasingly cost-effective, making them competitive with traditional energy sources in many markets.</p>

<p>Solar panel efficiency has improved dramatically over the past decade, while manufacturing costs have dropped significantly. This has made solar power accessible to both large utilities and individual homeowners. In sunny regions, solar energy can now provide electricity at lower costs than coal or natural gas.</p>

<p>Wind power has also seen remarkable growth. Offshore wind farms, in particular, can harness stronger and more consistent winds, generating substantial amounts of electricity. Countries like Denmark and Scotland are leading the way, with wind power providing a large percentage of their electricity needs.</p>

<p>Energy storage remains a challenge for renewable power. Solar and wind are intermittent sources, producing electricity only when the sun shines or wind blows. Battery technology is advancing rapidly, but large-scale storage solutions are still needed to make renewable energy fully reliable.</p>',
                'questionStart' => 14,
                'questionEnd' => 27,
                'questions' => $this->generateReadingQuestions(14, 27)
            ],
            [
                'number' => 3,
                'title' => 'The Science of Sleep',
                'subtitle' => 'Understanding why we need rest',
                'content' => '<p>Sleep is essential for human health and well-being, yet many people do not get enough of it. Scientists have identified several critical functions that occur during sleep, from memory consolidation to cellular repair.</p>

<p>During sleep, the brain processes and consolidates memories from the day. Different stages of sleep serve different purposes. Deep sleep is crucial for physical restoration, while REM (rapid eye movement) sleep is important for cognitive functions and emotional regulation.</p>

<p>Chronic sleep deprivation has serious consequences. It impairs cognitive function, weakens the immune system, and increases the risk of various health problems including obesity, diabetes, and cardiovascular disease. Even mild sleep loss can affect mood, attention, and decision-making abilities.</p>

<p>Modern lifestyles often interfere with good sleep. The use of electronic devices before bedtime, irregular schedules, and high stress levels all contribute to sleep problems. Sleep scientists recommend maintaining consistent sleep schedules, creating a dark and quiet sleep environment, and avoiding screens before bedtime to improve sleep quality.</p>',
                'questionStart' => 28,
                'questionEnd' => 40,
                'questions' => $this->generateReadingQuestions(28, 40)
            ]
        ];

        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Reading Practice Test 2',
            'description' => 'Full IELTS Academic Reading test with 3 passages on contemporary issues',
            'instructions' => '<p>You should spend about 60 minutes on this task. Read the three passages and answer all questions. Write your answers on the answer sheet.</p>',
            'type' => 'ielts',
            'subtype' => 'reading',
            'time_limit' => 60,
            'passing_score' => 50.00,
            'total_points' => 40,
            'max_attempts' => 3,
            'settings' => json_encode([
                'passages' => $passages2
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 2 complete Reading tests (40 questions each)\n";
    }

    private function generateReadingQuestions($start, $end)
    {
        $questions = [];
        $types = ['true_false_ng', 'multiple_choice', 'sentence_completion', 'matching'];
        
        for ($i = $start; $i <= $end; $i++) {
            $type = $types[($i - $start) % count($types)];
            
            $question = [
                'number' => $i,
                'type' => $type
            ];

            switch ($type) {
                case 'true_false_ng':
                    $question['content'] = "Statement $i for True/False/Not Given";
                    $question['statement'] = "This is statement number $i";
                    break;
                case 'multiple_choice':
                    $question['content'] = "Multiple choice question $i";
                    $question['options'] = [
                        ['value' => 'A', 'text' => 'Option A'],
                        ['value' => 'B', 'text' => 'Option B'],
                        ['value' => 'C', 'text' => 'Option C'],
                        ['value' => 'D', 'text' => 'Option D']
                    ];
                    break;
                case 'sentence_completion':
                    $question['content'] = "Complete the sentence for question $i";
                    $question['prefix'] = "The passage states that";
                    $question['suffix'] = "is important.";
                    break;
                case 'matching':
                    $question['content'] = "Match the information for question $i";
                    break;
            }

            $questions[] = $question;
        }

        return $questions;
    }

    private function createWritingTests()
    {
        echo "📝 Creating 2 Full IELTS Writing Tests (2 tasks each)...\n";

        // WRITING TEST 1
        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Writing Practice Test 1',
            'description' => 'Complete IELTS Academic Writing test with Task 1 (graph) and Task 2 (essay)',
            'instructions' => '<p>This test contains TWO tasks. You must complete BOTH tasks.</p><ul><li><strong>Task 1:</strong> Spend about 20 minutes. Write at least 150 words.</li><li><strong>Task 2:</strong> Spend about 40 minutes. Write at least 250 words.</li></ul>',
            'type' => 'ielts',
            'subtype' => 'writing',
            'time_limit' => 60,
            'passing_score' => 50.00,
            'total_points' => 9,
            'max_attempts' => 3,
            'settings' => json_encode([
                'tasks' => [
                    [
                        'number' => 1,
                        'title' => 'Task 1: Bar Chart Analysis',
                        'instruction' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant.',
                        'content' => '<div class="bg-gray-100 p-6 rounded-lg border-2 border-gray-300 my-4">
                            <p class="font-semibold mb-3">The bar chart below shows the percentage of Australian men and women in different age groups who did regular physical activity in 2010.</p>
                            <div class="bg-white p-4 rounded border border-gray-400 text-center">
                                <p class="text-gray-600 italic">[Bar Chart Image Would Appear Here]</p>
                                <p class="text-sm mt-2">Data showing: 15-24: M 52%, W 47% | 25-34: M 42%, W 48% | 35-44: M 39%, W 52% | 45-54: M 43%, W 53% | 55-64: M 45%, W 53% | 65+: M 46%, W 47%</p>
                            </div>
                        </div>',
                        'minWords' => 150,
                        'timeRecommendation' => 20
                    ],
                    [
                        'number' => 2,
                        'title' => 'Task 2: Opinion Essay',
                        'instruction' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience.',
                        'content' => '<div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-300 my-4">
                            <p class="font-semibold mb-3">Some people believe that professionals, such as doctors and engineers, should be required to work in the country where they did their training. Others believe they should be free to work in another country if they wish.</p>
                            <p class="mt-3"><strong>Discuss both these views and give your own opinion.</strong></p>
                        </div>',
                        'minWords' => 250,
                        'timeRecommendation' => 40
                    ]
                ]
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        // WRITING TEST 2
        DB::table('tests')->insert([
            'uuid' => Str::uuid(),
            'title' => 'IELTS Writing Practice Test 2',
            'description' => 'Complete IELTS Academic Writing test with Task 1 (process diagram) and Task 2 (argument essay)',
            'instructions' => '<p>This test contains TWO tasks. You must complete BOTH tasks.</p><ul><li><strong>Task 1:</strong> Spend about 20 minutes. Write at least 150 words.</li><li><strong>Task 2:</strong> Spend about 40 minutes. Write at least 250 words.</li></ul>',
            'type' => 'ielts',
            'subtype' => 'writing',
            'time_limit' => 60,
            'passing_score' => 50.00,
            'total_points' => 9,
            'max_attempts' => 3,
            'settings' => json_encode([
                'tasks' => [
                    [
                        'number' => 1,
                        'title' => 'Task 1: Line Graph Description',
                        'instruction' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant.',
                        'content' => '<div class="bg-gray-100 p-6 rounded-lg border-2 border-gray-300 my-4">
                            <p class="font-semibold mb-3">The graph below shows the consumption of three types of fast food by teenagers in Mauritius from 1985 to 2015.</p>
                            <div class="bg-white p-4 rounded border border-gray-400 text-center">
                                <p class="text-gray-600 italic">[Line Graph Image Would Appear Here]</p>
                                <p class="text-sm mt-2">Pizza consumption increased from 5 to 85 times/year | Hamburgers fluctuated between 10-50 times/year | Fish & Chips decreased from 80 to 20 times/year</p>
                            </div>
                        </div>',
                        'minWords' => 150,
                        'timeRecommendation' => 20
                    ],
                    [
                        'number' => 2,
                        'title' => 'Task 2: Argumentative Essay',
                        'instruction' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience.',
                        'content' => '<div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-300 my-4">
                            <p class="font-semibold mb-3">In many countries, the amount of crime is increasing.</p>
                            <p class="mt-3"><strong>What do you think are the main causes of crime? How can we deal with those causes?</strong></p>
                        </div>',
                        'minWords' => 250,
                        'timeRecommendation' => 40
                    ]
                ]
            ]),
            'branch_id' => $this->branchId,
            'created_by' => $this->userId,
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        echo "  ✓ Created 2 complete Writing tests (2 tasks each)\n";
    }
}

