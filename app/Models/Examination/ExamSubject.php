<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Categories/skills within this subject
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ExamSubjectCategory::class, 'subject_id')->orderBy('sort_order');
    }

    /**
     * Questions in this subject
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subject_id');
    }

    /**
     * Tests in this subject
     */
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class, 'subject_id');
    }

    /**
     * Scope for active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get predefined subjects with their categories
     */
    public static function getDefaultSubjects(): array
    {
        return [
            [
                'name' => 'Tiếng Anh',
                'code' => 'english',
                'description' => 'Môn học Tiếng Anh với các kỹ năng Nghe, Nói, Đọc, Viết',
                'icon' => 'language',
                'color' => '#3B82F6',
                'categories' => [
                    ['name' => 'Listening', 'code' => 'listening', 'description' => 'Kỹ năng nghe'],
                    ['name' => 'Speaking', 'code' => 'speaking', 'description' => 'Kỹ năng nói'],
                    ['name' => 'Reading', 'code' => 'reading', 'description' => 'Kỹ năng đọc'],
                    ['name' => 'Writing', 'code' => 'writing', 'description' => 'Kỹ năng viết'],
                    ['name' => 'Grammar', 'code' => 'grammar', 'description' => 'Ngữ pháp'],
                    ['name' => 'Vocabulary', 'code' => 'vocabulary', 'description' => 'Từ vựng'],
                ],
            ],
            [
                'name' => 'Toán học',
                'code' => 'math',
                'description' => 'Môn học Toán',
                'icon' => 'calculator',
                'color' => '#10B981',
                'categories' => [
                    ['name' => 'Đại số', 'code' => 'algebra', 'description' => 'Đại số'],
                    ['name' => 'Hình học', 'code' => 'geometry', 'description' => 'Hình học'],
                    ['name' => 'Giải tích', 'code' => 'calculus', 'description' => 'Giải tích'],
                    ['name' => 'Xác suất thống kê', 'code' => 'statistics', 'description' => 'Xác suất thống kê'],
                ],
            ],
            [
                'name' => 'Vật lý',
                'code' => 'physics',
                'description' => 'Môn học Vật lý',
                'icon' => 'atom',
                'color' => '#F59E0B',
                'categories' => [
                    ['name' => 'Cơ học', 'code' => 'mechanics', 'description' => 'Cơ học'],
                    ['name' => 'Điện học', 'code' => 'electricity', 'description' => 'Điện học'],
                    ['name' => 'Quang học', 'code' => 'optics', 'description' => 'Quang học'],
                    ['name' => 'Nhiệt học', 'code' => 'thermodynamics', 'description' => 'Nhiệt học'],
                ],
            ],
            [
                'name' => 'Hóa học',
                'code' => 'chemistry',
                'description' => 'Môn học Hóa học',
                'icon' => 'flask',
                'color' => '#EF4444',
                'categories' => [
                    ['name' => 'Hóa vô cơ', 'code' => 'inorganic', 'description' => 'Hóa vô cơ'],
                    ['name' => 'Hóa hữu cơ', 'code' => 'organic', 'description' => 'Hóa hữu cơ'],
                    ['name' => 'Hóa đại cương', 'code' => 'general', 'description' => 'Hóa đại cương'],
                ],
            ],
        ];
    }
}
