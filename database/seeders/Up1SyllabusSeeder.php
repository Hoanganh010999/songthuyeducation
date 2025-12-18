<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LessonPlan;
use App\Models\LessonPlanSession;
use App\Models\Subject;
use App\Models\Branch;

class Up1SyllabusSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create English subject
        $subject = Subject::where('code', 'ENG')->first();
        if (!$subject) {
            $subject = Subject::create([
                'name' => 'Tiếng Anh',
                'code' => 'ENG',
                'branch_id' => Branch::first()->id,
                'description' => 'English Language',
                'is_active' => true
            ]);
        }

        // Create or Update Up 1 Syllabus (use existing code "Up 1", not "UP-1")
        $syllabus = LessonPlan::updateOrCreate(
            ['code' => 'Up 1'],  // Use the existing code
            [
                'branch_id' => Branch::first()->id,
                'subject_id' => $subject->id,
                'name' => 'Up 1',
                'total_sessions' => 32,
                'level' => 'beginner',
                'academic_year' => '2024-2025',
                'description' => 'Khóa học tiếng Anh cho trẻ em cấp độ cơ bản - Up 1',
                'created_by' => \App\Models\User::first()->id,
                'status' => 'in_use'
            ]
        );

        // Define 10 first sessions with sample data
        $sessions = [
            // Unit 1
            [
                'title' => 'Hello!',
                'objectives' => '<p>- Greet someone</p><p>- Introduce oneself</p><p>- Identify characters in the book</p>',
                'content' => '<p><strong>Từ vựng:</strong> Linda, Nam, Bessy, Bob</p><p><strong>Mẫu câu:</strong> Hello, I\'m (Linda).</p><p><strong>Hoạt động:</strong></p><ul><li>Giới thiệu tên của mình</li><li>Chào hỏi bạn bè</li><li>Nhận diện nhân vật trong sách</li></ul>',
                'additional_resources' => [
                    'teacher_name' => '',
                    'lesson_focus' => 'Greetings and Introductions',
                    'level' => 'Beginner',
                    'tp_number' => '',
                    'lesson_date' => '',
                    'communicative_outcome' => 'By the end of the lesson, students will be able to greet others and introduce themselves using "Hello, I\'m..."',
                    'linguistic_aim' => 'By the end of the lesson, students will have practiced the greeting "Hello" and the introduction pattern "I\'m [name]"',
                    'productive_subskills_focus' => 'Fluency in simple greetings and self-introduction',
                    'receptive_subskills_focus' => 'Understanding basic greetings and names',
                    'teaching_aspects_to_improve' => '',
                    'improvement_methods' => '',
                    'framework_shape' => 'D',
                    'language_area' => 'Greetings and Personal Information',
                    'examples_of_language' => '1) Hello! 2) I\'m Linda. 3) I\'m Nam.',
                    'context' => 'Classroom introductions, meeting new classmates',
                    'concept_checking_methods' => 'Role-play greetings, visual flashcards of characters',
                    'concept_checking_in_lesson' => 'Students will practice introducing themselves to classmates and identifying characters in the book through pictures'
                ]
            ],

            // Unit 2
            [
                'title' => 'What\'s your name?',
                'objectives' => '<p>- Ask someone\'s name</p><p>- Say one\'s name</p><p>- Spell one\'s name</p>',
                'content' => '<p><strong>Từ vựng:</strong> name, spell, alphabet (A-Z)</p><p><strong>Mẫu câu:</strong></p><ul><li>What\'s your name?</li><li>My name is...</li><li>How do you spell it?</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Hỏi và trả lời tên</li><li>Đánh vần tên của mình</li><li>Làm quen với bảng chữ cái</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Asking and saying names', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to ask for and give their names', 'linguistic_aim' => 'Practice question form "What\'s your name?" and response "My name is..."', 'productive_subskills_focus' => 'Pronunciation of alphabet letters', 'receptive_subskills_focus' => 'Understanding name-related questions', 'framework_shape' => 'D', 'language_area' => 'Personal Information - Names']
            ],

            // Unit 3
            [
                'title' => 'How old are you?',
                'objectives' => '<p>- Ask and answer about age</p><p>- Count numbers 1-10</p><p>- Talk about birthday</p>',
                'content' => '<p><strong>Từ vựng:</strong> Numbers 1-10, age, old, year, birthday</p><p><strong>Mẫu câu:</strong></p><ul><li>How old are you?</li><li>I\'m (six) years old.</li><li>When is your birthday?</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Đếm số từ 1-10</li><li>Hỏi và trả lời về tuổi</li><li>Nói về sinh nhật</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Numbers 1-10 and Age', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to ask about and state their age', 'linguistic_aim' => 'Learn numbers 1-10 and the structure "I\'m X years old"', 'productive_subskills_focus' => 'Fluency in counting and stating age', 'receptive_subskills_focus' => 'Understanding age-related questions', 'framework_shape' => 'A', 'language_area' => 'Numbers and Personal Information']
            ],

            // Unit 4
            [
                'title' => 'This is my family',
                'objectives' => '<p>- Identify family members</p><p>- Introduce family members</p><p>- Describe family relationships</p>',
                'content' => '<p><strong>Từ vựng:</strong> father, mother, brother, sister, grandfather, grandmother, family</p><p><strong>Mẫu câu:</strong></p><ul><li>This is my (father).</li><li>Who is this?</li><li>This is my family.</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Giới thiệu thành viên gia đình</li><li>Vẽ cây gia đình</li><li>Nói về gia đình của mình</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Family Members', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to introduce their family members', 'linguistic_aim' => 'Learn family vocabulary and the pattern "This is my..."', 'productive_subskills_focus' => 'Clear pronunciation of family words', 'receptive_subskills_focus' => 'Identifying family members by name', 'framework_shape' => 'D', 'language_area' => 'Family Vocabulary']
            ],

            // Unit 5
            [
                'title' => 'Colors around us',
                'objectives' => '<p>- Identify and name colors</p><p>- Describe objects by color</p><p>- Express color preferences</p>',
                'content' => '<p><strong>Từ vựng:</strong> red, blue, yellow, green, orange, purple, pink, black, white, brown</p><p><strong>Mẫu câu:</strong></p><ul><li>What color is it?</li><li>It\'s (red).</li><li>I like (blue).</li><li>My favorite color is...</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Nhận diện màu sắc</li><li>Tô màu theo chỉ dẫn</li><li>Nói về màu yêu thích</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Colors', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to name colors and describe objects by color', 'linguistic_aim' => 'Learn color vocabulary and the questions "What color is it?"', 'productive_subskills_focus' => 'Accuracy in color names', 'receptive_subskills_focus' => 'Identifying colors in pictures', 'framework_shape' => 'D', 'language_area' => 'Colors and Adjectives']
            ],

            // Unit 6
            [
                'title' => 'My toys',
                'objectives' => '<p>- Identify common toys</p><p>- Talk about ownership</p><p>- Ask and answer about toys</p>',
                'content' => '<p><strong>Từ vựng:</strong> ball, doll, car, robot, teddy bear, kite, puzzle, toy</p><p><strong>Mẫu câu:</strong></p><ul><li>This is my (ball).</li><li>What toy do you have?</li><li>I have a (robot).</li><li>Do you have a (doll)?</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Giới thiệu đồ chơi của mình</li><li>Hỏi về đồ chơi của bạn</li><li>Mô tả đồ chơi yêu thích</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Toys and Possessions', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to talk about their toys using possessive forms', 'linguistic_aim' => 'Learn toy vocabulary and patterns "I have..." and "Do you have...?"', 'productive_subskills_focus' => 'Using possessive "my" correctly', 'receptive_subskills_focus' => 'Understanding questions about possessions', 'framework_shape' => 'D', 'language_area' => 'Possessive Forms and Toys']
            ],

            // Unit 7
            [
                'title' => 'At school',
                'objectives' => '<p>- Identify school objects</p><p>- Talk about classroom items</p><p>- Use classroom language</p>',
                'content' => '<p><strong>Từ vựng:</strong> book, pen, pencil, ruler, eraser, bag, desk, chair, teacher, student</p><p><strong>Mẫu câu:</strong></p><ul><li>What is this?</li><li>It\'s a (book).</li><li>I have a (pencil).</li><li>May I borrow your (eraser)?</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Nhận diện đồ dùng học tập</li><li>Tìm đồ vật trong lớp</li><li>Thực hành hỏi mượn đồ</li></ul>',
                'additional_resources' => ['lesson_focus' => 'School Objects', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to identify and request classroom items', 'linguistic_aim' => 'Learn school vocabulary and polite requests with "May I...?"', 'productive_subskills_focus' => 'Using polite classroom language', 'receptive_subskills_focus' => 'Understanding classroom instructions', 'framework_shape' => 'D', 'language_area' => 'Classroom Vocabulary']
            ],

            // Unit 8
            [
                'title' => 'Body parts',
                'objectives' => '<p>- Name body parts</p><p>- Describe physical features</p><p>- Sing songs about body</p>',
                'content' => '<p><strong>Từ vựng:</strong> head, face, eyes, nose, mouth, ears, hands, feet, arms, legs</p><p><strong>Mẫu câu:</strong></p><ul><li>Touch your (head).</li><li>I have two (eyes).</li><li>This is my (nose).</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Chỉ và nói tên các bộ phận cơ thể</li><li>Hát bài "Head, Shoulders, Knees and Toes"</li><li>Vẽ và gắn nhãn cơ thể người</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Body Parts', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to name and point to body parts', 'linguistic_aim' => 'Learn body part vocabulary and commands like "Touch your..."', 'productive_subskills_focus' => 'Following and giving simple commands', 'receptive_subskills_focus' => 'Understanding body-related instructions', 'framework_shape' => 'F', 'language_area' => 'Body Vocabulary and Commands']
            ],

            // Unit 9
            [
                'title' => 'Animals I like',
                'objectives' => '<p>- Identify common animals</p><p>- Describe animals</p><p>- Express preferences about animals</p>',
                'content' => '<p><strong>Từ vựng:</strong> dog, cat, bird, fish, rabbit, monkey, elephant, tiger, lion, bear</p><p><strong>Mẫu câu:</strong></p><ul><li>What is it?</li><li>It\'s a (dog).</li><li>I like (cats).</li><li>Do you like (birds)?</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Nhận diện động vật</li><li>Bắt chước tiếng kêu động vật</li><li>Nói về động vật yêu thích</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Animals', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to name animals and express preferences', 'linguistic_aim' => 'Learn animal vocabulary and the pattern "I like..."', 'productive_subskills_focus' => 'Expressing likes and dislikes', 'receptive_subskills_focus' => 'Identifying animals by sound and picture', 'framework_shape' => 'D', 'language_area' => 'Animals and Preferences']
            ],

            // Unit 10
            [
                'title' => 'What can you do?',
                'objectives' => '<p>- Talk about abilities</p><p>- Use "can" and "can\'t"</p><p>- Describe actions</p>',
                'content' => '<p><strong>Từ vựng:</strong> run, jump, swim, fly, sing, dance, draw, read, write, play</p><p><strong>Mẫu câu:</strong></p><ul><li>I can (run).</li><li>I can\'t (fly).</li><li>Can you (swim)?</li><li>Yes, I can. / No, I can\'t.</li></ul><p><strong>Hoạt động:</strong></p><ul><li>Nói về kỹ năng của mình</li><li>Hỏi bạn về khả năng</li><li>Chơi trò "Simon says"</li></ul>',
                'additional_resources' => ['lesson_focus' => 'Modal Verb "can"', 'level' => 'Beginner', 'communicative_outcome' => 'Students will be able to talk about abilities using can/can\'t', 'linguistic_aim' => 'Learn action verbs and the modal verb "can" for ability', 'productive_subskills_focus' => 'Fluency in describing abilities', 'receptive_subskills_focus' => 'Understanding questions about ability', 'framework_shape' => 'C', 'language_area' => 'Modal Verbs and Action Verbs']
            ],
        ];

        // Delete existing sessions for this lesson plan if re-seeding
        LessonPlanSession::where('lesson_plan_id', $syllabus->id)
            ->where('session_number', '<=', 10)
            ->delete();

        // Create all 10 sessions
        foreach ($sessions as $index => $session) {
            LessonPlanSession::create([
                'lesson_plan_id' => $syllabus->id,
                'session_number' => $index + 1,
                'lesson_title' => $session['title'],
                'lesson_objectives' => $session['objectives'],
                'lesson_content' => $session['content'],
                'additional_resources' => $session['additional_resources'] ?? null,
                'duration_minutes' => 45,
                'notes' => 'Unit ' . ($index + 1) . ' of Up 1 Course'
            ]);
        }

        $this->command->info('✓ Up 1 Syllabus with first 10 units created successfully!');
    }
}
