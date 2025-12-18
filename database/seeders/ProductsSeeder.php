<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $createdBy = $admin?->id ?? 1;

        $products = [
            // English Courses
            [
                'name' => 'Khóa học Tiếng Anh Thiếu Nhi (3-5 tuổi)',
                'description' => 'Khóa học tiếng Anh cho trẻ mầm non, tập trung phát triển kỹ năng nghe - nói qua các hoạt động vui chơi, trò chơi, bài hát.',
                'type' => 'course',
                'price' => 3000000,
                'sale_price' => 2700000,
                'duration_months' => 3,
                'total_sessions' => 36,
                'category' => 'english',
                'level' => 'beginner',
                'target_ages' => [3, 4, 5],
                'is_featured' => true,
                'allow_trial' => true,
                'created_by' => $createdBy,
            ],
            [
                'name' => 'Khóa học Tiếng Anh Tiểu Học (6-10 tuổi)',
                'description' => 'Chương trình tiếng Anh toàn diện cho học sinh tiểu học, phát triển 4 kỹ năng: Nghe, Nói, Đọc, Viết.',
                'type' => 'course',
                'price' => 4500000,
                'duration_months' => 6,
                'total_sessions' => 72,
                'category' => 'english',
                'level' => 'intermediate',
                'target_ages' => [6, 7, 8, 9, 10],
                'is_featured' => true,
                'allow_trial' => true,
                'created_by' => $createdBy,
            ],
            [
                'name' => 'Khóa học Tiếng Anh TOEIC',
                'description' => 'Luyện thi TOEIC đạt 600+ điểm, tập trung vào kỹ năng nghe và đọc hiểu.',
                'type' => 'course',
                'price' => 6000000,
                'sale_price' => 5400000,
                'duration_months' => 4,
                'total_sessions' => 48,
                'category' => 'english',
                'level' => 'advanced',
                'target_ages' => [16, 17, 18, 19, 20, 21, 22],
                'is_featured' => true,
                'allow_trial' => false,
                'created_by' => $createdBy,
            ],

            // Math Courses
            [
                'name' => 'Toán Tư Duy Cho Trẻ (5-7 tuổi)',
                'description' => 'Phát triển tư duy logic, khả năng giải quyết vấn đề qua các bài toán thực tế và trò chơi.',
                'type' => 'course',
                'price' => 3500000,
                'duration_months' => 4,
                'total_sessions' => 48,
                'category' => 'math',
                'level' => 'beginner',
                'target_ages' => [5, 6, 7],
                'is_featured' => true,
                'allow_trial' => true,
                'created_by' => $createdBy,
            ],
            [
                'name' => 'Toán Nâng Cao Tiểu Học',
                'description' => 'Chương trình toán nâng cao cho học sinh tiểu học, chuẩn bị cho các kỳ thi học sinh giỏi.',
                'type' => 'course',
                'price' => 5000000,
                'duration_months' => 6,
                'total_sessions' => 60,
                'category' => 'math',
                'level' => 'advanced',
                'target_ages' => [8, 9, 10, 11],
                'is_featured' => false,
                'allow_trial' => true,
                'created_by' => $createdBy,
            ],

            // Science Courses
            [
                'name' => 'Khoa Học Khám Phá (6-9 tuổi)',
                'description' => 'Khóa học khoa học thực nghiệm, giúp trẻ khám phá thế giới xung quanh qua các thí nghiệm thú vị.',
                'type' => 'course',
                'price' => 4000000,
                'duration_months' => 3,
                'total_sessions' => 36,
                'category' => 'science',
                'level' => 'beginner',
                'target_ages' => [6, 7, 8, 9],
                'is_featured' => false,
                'allow_trial' => true,
                'created_by' => $createdBy,
            ],

            // Packages
            [
                'name' => 'Gói Combo Tiếng Anh + Toán (Cả Năm)',
                'description' => 'Gói học tiết kiệm: Tiếng Anh + Toán Tư Duy, thời lượng 12 tháng.',
                'type' => 'package',
                'price' => 15000000,
                'sale_price' => 12000000,
                'duration_months' => 12,
                'total_sessions' => 144,
                'category' => 'combo',
                'level' => 'intermediate',
                'target_ages' => [6, 7, 8, 9, 10],
                'is_featured' => true,
                'allow_trial' => false,
                'created_by' => $createdBy,
            ],

            // Materials
            [
                'name' => 'Bộ Sách Giáo Trình Tiếng Anh',
                'description' => 'Bộ 3 cuốn sách giáo trình + workbook + audio CD.',
                'type' => 'material',
                'price' => 500000,
                'duration_months' => null,
                'total_sessions' => null,
                'category' => 'english',
                'level' => null,
                'target_ages' => [6, 7, 8, 9, 10, 11, 12],
                'is_featured' => false,
                'allow_trial' => false,
                'created_by' => $createdBy,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
            $this->command->info("✓ Product: {$productData['name']}");
        }

        $this->command->info("\n✅ Products seeded successfully!");
    }
}

