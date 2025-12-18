<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\CourseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningHistoryController extends Controller
{
    // Get learning history for a student
    public function getStudentHistory(Request $request, $studentId)
    {
        try {
            $student = Student::with(['user', 'classes'])->findOrFail($studentId);

            $classId = $request->class_id;

            // 🔥 FIX: attendances.student_id stores user_id, not students.id
            $userId = $student->user_id;

            Log::info('[LearningHistory] Loading student history', [
                'student_id' => $studentId,
                'user_id' => $userId,
                'class_id' => $classId,
            ]);

            // Attendance history (use user_id, not student.id)
            $attendances = Attendance::with(['session', 'session.class'])
                ->where('student_id', $userId) // 🔥 FIX: Use user_id
                ->when($classId, fn($q) => $q->whereHas('session', fn($sq) => $sq->where('class_id', $classId)))
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            Log::info('[LearningHistory] Attendances loaded', ['count' => $attendances->count()]);

            // Submissions history (course_submissions.student_id stores students.id, not user_id)
            $submissions = CourseSubmission::with(['assignment', 'assignment.class', 'grader'])
                ->where('student_id', $studentId) // Keep using students.id
                ->when($classId, fn($q) => $q->whereHas('assignment', fn($q) => $q->where('class_id', $classId)))
                ->orderBy('submitted_at', 'desc')
                ->limit(50)
                ->get();

            Log::info('[LearningHistory] Submissions loaded', ['count' => $submissions->count()]);

            // Evaluation results from attendance data
            $evaluations = $attendances
                ->filter(fn($att) => !empty($att->evaluation_data))
                ->map(fn($att) => [
                    'id' => $att->id,
                    'session_id' => $att->session_id,
                    'student_id' => $att->student_id,
                    'evaluation_data' => $att->evaluation_data,
                    'evaluation_pdf_url' => $att->evaluation_pdf_url,
                    'session' => $att->session,
                    'created_at' => $att->created_at,
                ])
                ->values();

            // Summary statistics
            $stats = [
                'total_sessions' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'excused_count' => $attendances->where('status', 'excused')->count(),
                'total_assignments' => $submissions->count(),
                'submitted_count' => $submissions->whereNotNull('submitted_at')->count(),
                'graded_count' => $submissions->where('status', 'graded')->count(),
                'average_score' => $submissions->whereNotNull('score')->avg('score'), // 🔥 FIX: Use 'score' not 'grade'
                'total_evaluations' => $evaluations->count(), // 🔥 NEW: Add evaluation count
            ];

            Log::info('[LearningHistory] Statistics calculated', $stats);

            return response()->json([
                'student' => $student,
                'attendances' => $attendances,
                'submissions' => $submissions,
                'evaluations' => $evaluations,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading learning history', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error loading learning history'], 500);
        }
    }

    // Get learning history for entire class
    public function getClassHistory(Request $request, $classId)
    {
        try {
            Log::info('[LearningHistory] Loading class history', [
                'class_id' => $classId,
                'user_id' => $request->user()->id,
            ]);

            // Get all attendances for this class
            Log::info('[LearningHistory] Loading attendances...');
            $attendances = Attendance::with([
                    'session' => function($query) {
                        $query->select('id', 'class_id', 'scheduled_date', 'actual_date', 'start_time', 'end_time', 'status');
                    },
                    'student' => function($query) {
                        $query->select('id', 'name', 'email');
                    }
                ])
                ->whereHas('session', fn($q) => $q->where('class_id', $classId))
                ->orderBy('created_at', 'desc')
                ->limit(200)
                ->get();
            
            Log::info('[LearningHistory] Attendances loaded', ['count' => $attendances->count()]);

            // Get all submissions for this class
            Log::info('[LearningHistory] Loading submissions...');
            $submissions = CourseSubmission::with([
                    'assignment' => function($query) {
                        $query->select('id', 'class_id', 'title', 'due_date');
                    },
                    'student' => function($query) {
                        $query->select('id', 'user_id', 'student_code');
                    },
                    'student.user' => function($query) {
                        $query->select('id', 'name', 'email');
                    },
                    'grader' => function($query) {
                        $query->select('id', 'name', 'email');
                    }
                ])
                ->whereHas('assignment', fn($q) => $q->where('class_id', $classId))
                ->orderBy('submitted_at', 'desc')
                ->limit(200)
                ->get();
            
            Log::info('[LearningHistory] Submissions loaded', ['count' => $submissions->count()]);

            // Evaluation results from attendance data
            Log::info('[LearningHistory] Processing evaluations...');
            $evaluations = $attendances
                ->filter(fn($att) => !empty($att->evaluation_data))
                ->map(fn($att) => [
                    'id' => $att->id,
                    'session_id' => $att->session_id,
                    'student_id' => $att->student_id,
                    'student' => $att->student,
                    'evaluation_data' => $att->evaluation_data,
                    'evaluation_pdf_url' => $att->evaluation_pdf_url,
                    'session' => $att->session,
                    'created_at' => $att->created_at,
                ])
                ->values();
            
            Log::info('[LearningHistory] Evaluations processed', ['count' => $evaluations->count()]);

            // Summary statistics
            Log::info('[LearningHistory] Calculating statistics...');
            $stats = [
                'total_sessions' => $attendances->unique('session_id')->count(),
                'total_students' => $attendances->unique('student_id')->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'excused_count' => $attendances->where('status', 'excused')->count(),
                'total_assignments' => $submissions->unique('assignment_id')->count(),
                'submitted_count' => $submissions->whereNotNull('submitted_at')->count(),
                'graded_count' => $submissions->where('status', 'graded')->count(), 
                'average_score' => $submissions->whereNotNull('score')->avg('score'), // 🔥 FIX: Use 'score' not 'grade'
                'total_evaluations' => $evaluations->count(), // 🔥 NEW: Add evaluation count
            ];
            
            Log::info('[LearningHistory] Statistics calculated', $stats);
            Log::info('[LearningHistory] Class history loaded successfully');

            return response()->json([
                'attendances' => $attendances,
                'submissions' => $submissions,
                'evaluations' => $evaluations,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('[LearningHistory] Error loading class history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'class_id' => $classId,
            ]);
            return response()->json([
                'message' => 'Error loading class history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get class-wide statistics
    public function getClassStats($classId)
    {
        try {
            $stats = [
                'total_posts' => DB::table('course_posts')->where('class_id', $classId)->count(),
                'total_assignments' => DB::table('course_assignments')->where('class_id', $classId)->count(),
                'total_students' => DB::table('class_students')->where('class_id', $classId)->count(),
                'attendance_rate' => $this->calculateAttendanceRate($classId),
                'submission_rate' => $this->calculateSubmissionRate($classId),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error loading class stats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error loading class stats'], 500);
        }
    }

    private function calculateAttendanceRate($classId)
    {
        $total = Attendance::whereHas('session', fn($q) => $q->where('class_id', $classId))->count();
        if ($total === 0) return 0;
        
        $present = Attendance::whereHas('session', fn($q) => $q->where('class_id', $classId))
            ->where('status', 'present')
            ->count();
        return round(($present / $total) * 100, 2);
    }

    private function calculateSubmissionRate($classId)
    {
        $totalAssignments = DB::table('course_assignments')
            ->where('class_id', $classId)
            ->where('status', 'published')
            ->count();
        
        if ($totalAssignments === 0) return 0;
        
        $totalStudents = DB::table('class_students')->where('class_id', $classId)->count();
        $expectedSubmissions = $totalAssignments * $totalStudents;
        
        if ($expectedSubmissions === 0) return 0;
        
        $actualSubmissions = DB::table('course_submissions')
            ->whereIn('assignment_id', function($q) use ($classId) {
                $q->select('id')->from('course_assignments')->where('class_id', $classId);
            })
            ->where('status', 'submitted')
            ->count();
        
        return round(($actualSubmissions / $expectedSubmissions) * 100, 2);
    }
}
