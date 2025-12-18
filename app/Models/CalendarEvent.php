<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\QualitySetting;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventable_type',
        'eventable_id',
        'title',
        'description',
        'category',
        'start_date',
        'end_date',
        'is_all_day',
        'status',
        'user_id',
        'assigned_teacher_id',
        'branch_id',
        'created_by',
        'manager_id',
        'attendees',
        'color',
        'icon',
        'location',
        'has_reminder',
        'reminder_minutes_before',
        'metadata',
        'test_result',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
        'has_reminder' => 'boolean',
        'attendees' => 'array',
        'metadata' => 'array',
        'test_result' => 'array',
    ];

    /**
     * Polymorphic relationship: Event có thể thuộc về bất kỳ model nào
     */
    public function eventable()
    {
        return $this->morphTo();
    }

    /**
     * Relationship: User (Người tạo/chịu trách nhiệm)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: Created By (Người tạo event)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Manager (Quản lý trực tiếp)
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relationship: Assigned Teacher (Giáo viên được gán cho placement test)
     */
    public function assignedTeacher()
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }

    /**
     * Scope: Filter theo category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter theo status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter theo user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter theo branch
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: Filter theo nhiều branches
     */
    public function scopeByBranches($query, array $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: Events mà user có quyền xem
     *
     * PERMISSION LEVELS:
     * 1. calendar.view_all: Xem TẤT CẢ events trong branch (super-admin)
     * 2. calendar.view_all_classes: Xem TẤT CẢ lịch lớp học trong branch
     * 3. Trưởng bộ phận (Department Head in teacher_department_ids): Xem TẤT CẢ lịch lớp học trong branch
     * 4. Trưởng bộ môn (Subject Head): Xem lịch lớp học của bộ môn mình quản lý
     * 5. Teacher: Xem lịch lớp mình dạy + events được tag
     * 6. Student: Xem lịch lớp mình học + events được tag
     * 7. Parent: Xem lịch lớp con học + events con được tag
     * 8. User: Events tự tạo + events được tag
     */
    public function scopeAccessibleBy($query, User $user)
    {
        \Log::info('[CalendarEvent] 🔵 accessibleBy scope called', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        // Get user's branch IDs
        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

        // 1️⃣ LEVEL 1: calendar.view_all - Xem TẤT CẢ events trong branch
        if ($user->hasPermission('calendar.view_all')) {
            \Log::info('[CalendarEvent] 👑 User has calendar.view_all permission', ['branch_ids' => $userBranchIds]);
            if (empty($userBranchIds)) {
                return $query; // No branch restriction
            }
            return $query->whereIn('branch_id', $userBranchIds);
        }

        // 2️⃣ LEVEL 2: calendar.view_all_classes - Xem TẤT CẢ lịch lớp học (class_session) trong branch
        if ($user->hasPermission('calendar.view_all_classes')) {
            \Log::info('[CalendarEvent] 📚 User has calendar.view_all_classes permission', ['branch_ids' => $userBranchIds]);
            if (empty($userBranchIds)) {
                return $query->where('category', 'class_session');
            }
            return $query->where('category', 'class_session')
                         ->whereIn('branch_id', $userBranchIds);
        }

        // 3️⃣ LEVEL 3: Trưởng bộ phận (Department Head in teacher_department_ids)
        // - Can see ALL class_session events in branch (regardless of subject)
        if ($this->isDepartmentHeadOfTeacherDepts($user)) {
            \Log::info('[CalendarEvent] 👔 User is department head of teacher departments', [
                'user_id' => $user->id,
                'branch_ids' => $userBranchIds
            ]);

            // Department head can see ALL class_session events + their own personal events
            return $query->where(function ($q) use ($user, $userBranchIds) {
                // All class_session events in their branches
                $q->where(function ($sub) use ($userBranchIds) {
                    $sub->where('category', 'class_session');
                    if (!empty($userBranchIds)) {
                        $sub->whereIn('branch_id', $userBranchIds);
                    }
                });

                // Plus their own events (created by them or tagged)
                $q->orWhere('user_id', $user->id);
                $q->orWhereRaw("JSON_CONTAINS(COALESCE(attendees, '[]'), ?)", [json_encode($user->id)]);
            });
        }

        // 4️⃣ LEVEL 4: Trưởng bộ môn (Subject Head) - Xem lịch lớp học của các lớp thuộc bộ môn mình quản lý
        $subjectHeadClassIds = $this->getSubjectHeadClassIds($user);

        // Check if user is a student
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        $studentClassIds = [];
        if ($student) {
            // Get classes student is enrolled in
            $studentClassIds = $student->classes()->pluck('classes.id')->toArray();
            \Log::info('[CalendarEvent] 👨‍🎓 User is student', [
                'student_id' => $student->id,
                'class_ids' => $studentClassIds
            ]);
        }

        // Check if user is a parent
        $parentClassIds = [];
        $childrenUserIds = [];
        $parent = \App\Models\ParentModel::where('user_id', $user->id)->first();
        if ($parent) {
            // Get all students (children) linked to this parent
            $childrenIds = $parent->students()->pluck('students.id')->toArray();
            // Get user_ids of children for checking attendees
            $childrenUserIds = $parent->students()
                ->whereNotNull('students.user_id')
                ->pluck('students.user_id')
                ->toArray();
            \Log::info('[CalendarEvent] 👪 User is parent', [
                'parent_id' => $parent->id,
                'children_ids' => $childrenIds,
                'children_user_ids' => $childrenUserIds
            ]);

            // Get all classes where any of their children are enrolled
            if (!empty($childrenIds)) {
                $parentClassIds = \App\Models\ClassModel::whereHas('students', function ($q) use ($childrenIds) {
                    $q->whereIn('class_students.student_id', $childrenIds)
                      ->where('class_students.status', 'active');
                })->pluck('id')->toArray();
                \Log::info('[CalendarEvent] 📚 Parent can view classes', ['class_ids' => $parentClassIds]);
            }
        }

        // Check if user is a teacher
        $teacherClassIds = [];
        if ($user->hasRole('teacher')) {
            // Get classes where user is homeroom teacher or assigned teacher
            $teacherClassIds = \App\Models\ClassModel::where('homeroom_teacher_id', $user->id)
                ->orWhereHas('teachers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->pluck('id')
                ->toArray();
            \Log::info('[CalendarEvent] 👨‍🏫 User is teacher', ['class_ids' => $teacherClassIds]);
        }

        // Combine all class IDs (including subject head classes)
        $classIds = array_unique(array_merge($studentClassIds, $parentClassIds, $teacherClassIds, $subjectHeadClassIds));

        if (!empty($subjectHeadClassIds)) {
            \Log::info('[CalendarEvent] 🎯 User is subject head', [
                'subject_head_class_ids' => $subjectHeadClassIds,
                'combined_class_ids' => $classIds
            ]);
        }

        \Log::info('[CalendarEvent] 📚 Combined class IDs', ['class_ids' => $classIds]);

        // User can see events where:
        // 1. They are the creator (user_id)
        // 2. They are tagged in attendees
        // 3. Their children are tagged in attendees (for parents)
        // 4. Event is a class_session of their class (as teacher, student, parent, or subject head)
        return $query->where(function ($q) use ($user, $classIds, $childrenUserIds) {
            // 1. Creator
            $q->where('user_id', $user->id)
              // 2. Tagged in attendees (JSON contains - for course events)
              ->orWhereRaw("JSON_CONTAINS(COALESCE(attendees, '[]'), ?)", [json_encode($user->id)]);

            // 3. Children are tagged in attendees (for parents)
            if (!empty($childrenUserIds)) {
                foreach ($childrenUserIds as $childUserId) {
                    $q->orWhereRaw("JSON_CONTAINS(COALESCE(attendees, '[]'), ?)", [json_encode($childUserId)]);
                }
            }

            // 4. Class-related events (teacher, student, parent, subject head)
            if (!empty($classIds)) {
                // Use JSON_EXTRACT to check if metadata->class_id is in user's classes
                $q->orWhereIn(DB::raw('JSON_EXTRACT(metadata, "$.class_id")'), $classIds);
            }
        });
    }

    /**
     * Scope: Events trong khoảng thời gian
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope: Events sắp tới (chưa hoàn thành)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
                     ->whereIn('status', ['pending', 'in_progress'])
                     ->orderBy('start_date', 'asc');
    }

    /**
     * Scope: Events quá hạn
     */
    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', now())
                     ->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Attribute: Is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->end_date < now() && in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Attribute: Is upcoming (trong 24h tới)
     */
    public function getIsUpcomingAttribute()
    {
        return $this->start_date > now() && $this->start_date <= now()->addDay();
    }

    /**
     * Attribute: Has test result
     */
    public function getHasTestResultAttribute()
    {
        return !empty($this->test_result);
    }

    /**
     * Static: Category colors mapping
     */
    public static function getCategoryColors(): array
    {
        return [
            'customer_follow_up' => '#F59E0B', // Amber - Liên hệ lại khách hàng
            'placement_test' => '#06B6D4', // Cyan - Lịch test đầu vào
            'class_session' => '#14B8A6', // Teal - Buổi học
            'meeting' => '#3B82F6', // Blue - Cuộc họp
            'task' => '#10B981', // Green - Công việc
            'deadline' => '#EF4444', // Red - Deadline
            'event' => '#8B5CF6', // Purple - Sự kiện
            'reminder' => '#EC4899', // Pink - Nhắc nhở
            'general' => '#6B7280', // Gray - Chung
        ];
    }

    /**
     * Static: Category icons mapping
     */
    public static function getCategoryIcons(): array
    {
        return [
            'customer_follow_up' => '📞',
            'placement_test' => '📝',
            'class_session' => '🎓',
            'meeting' => '👥',
            'task' => '✅',
            'deadline' => '⏰',
            'event' => '📅',
            'reminder' => '🔔',
            'general' => '📌',
        ];
    }

    /**
     * Helper: Check if user is department head of a department in teacher_department_ids
     *
     * Trưởng bộ phận (department_user.is_head = true) của các bộ phận
     * được thiết lập trong QualitySetting's teacher_department_ids
     * sẽ có quyền xem TẤT CẢ lịch lớp học trong branch (không phụ thuộc môn)
     */
    private function isDepartmentHeadOfTeacherDepts(User $user): bool
    {
        // Get teacher_department_ids from QualitySetting
        $teacherDeptSetting = QualitySetting::where('branch_id', $user->branch_id)
            ->where('industry', 'education')
            ->where('setting_key', 'teacher_department_ids')
            ->first();

        if (!$teacherDeptSetting) {
            return false;
        }

        $teacherDeptIds = $teacherDeptSetting->setting_value ?? [];

        if (empty($teacherDeptIds)) {
            return false;
        }

        // Check if user is head of any of these departments
        $isHeadOfTeacherDept = $user->departments()
            ->whereIn('departments.id', $teacherDeptIds)
            ->wherePivot('is_head', true)
            ->wherePivot('status', 'active')
            ->exists();

        return $isHeadOfTeacherDept;
    }

    /**
     * Helper: Get class IDs that user can view as subject head (trưởng bộ môn)
     *
     * Trưởng bộ môn (subject_teacher.is_head = true) có thể xem
     * tất cả lớp học dạy môn mà mình quản lý
     *
     * Classes can be linked to subjects via:
     * 1. class_subject table (many-to-many)
     * 2. classes.subject_id (direct relationship)
     * 3. lesson_plans.subject_id (via classes.lesson_plan_id)
     */
    private function getSubjectHeadClassIds(User $user): array
    {
        // Lấy các subject mà user là trưởng bộ môn (is_head = true trong subject_teacher)
        $headSubjectIds = DB::table('subject_teacher')
            ->where('user_id', $user->id)
            ->where('is_head', true)
            ->where('status', 'active')
            ->pluck('subject_id')
            ->toArray();

        if (empty($headSubjectIds)) {
            return [];
        }

        \Log::info('[CalendarEvent] 🎯 User is head of subjects', [
            'subject_ids' => $headSubjectIds
        ]);

        // Method 1: Get classes via class_subject table (many-to-many)
        $classIdsFromClassSubject = DB::table('class_subject')
            ->whereIn('subject_id', $headSubjectIds)
            ->where('status', 'active')
            ->pluck('class_id')
            ->toArray();

        // Method 2: Get classes with direct subject_id relationship
        $classIdsFromDirectSubject = DB::table('classes')
            ->whereIn('subject_id', $headSubjectIds)
            ->pluck('id')
            ->toArray();

        // Method 3: Get classes via lesson_plan.subject_id
        $classIdsFromLessonPlan = DB::table('classes')
            ->join('lesson_plans', 'classes.lesson_plan_id', '=', 'lesson_plans.id')
            ->whereIn('lesson_plans.subject_id', $headSubjectIds)
            ->pluck('classes.id')
            ->toArray();

        // Combine all class IDs
        $classIds = array_unique(array_merge(
            $classIdsFromClassSubject,
            $classIdsFromDirectSubject,
            $classIdsFromLessonPlan
        ));

        \Log::info('[CalendarEvent] 📚 Subject head can view classes', [
            'class_ids' => $classIds,
            'subject_ids' => $headSubjectIds,
            'from_class_subject' => count($classIdsFromClassSubject),
            'from_direct_subject' => count($classIdsFromDirectSubject),
            'from_lesson_plan' => count($classIdsFromLessonPlan),
        ]);

        return $classIds;
    }
}
