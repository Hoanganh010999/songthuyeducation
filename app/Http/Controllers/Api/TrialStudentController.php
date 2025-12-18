<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrialStudent;
use App\Models\Customer;
use App\Models\CustomerChild;
use App\Models\ClassModel;
use App\Models\ClassLessonSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrialStudentController extends Controller
{
    /**
     * Get available classes for trial
     */
    public function getAvailableClasses(Request $request)
    {
        $branchId = $request->input('branch_id');
        $level = $request->input('level');
        
        // Chỉ lấy các lớp active
        $query = ClassModel::with(['homeroomTeacher', 'subject', 'branch'])
            ->where('status', 'active')
            ->where('is_active', true);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($level) {
            $query->where('level', $level);
        }
        
        $classes = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Get available sessions for a class (chưa diễn ra)
     */
    public function getAvailableSessions($classId)
    {
        $sessions = ClassLessonSession::where('class_id', $classId)
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('session_number')
            ->get();
        
        // Add trial students count to each session
        $sessions->each(function ($session) {
            $session->trial_count = $session->activeTrialStudents()->count();
        });
        
        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Register for trial class
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'trialable_type' => 'required|in:customer,child',
            'trialable_id' => 'required|integer',
            'class_id' => 'required|exists:classes,id',
            'session_ids' => 'required|array|min:1',
            'session_ids.*' => 'exists:class_lesson_sessions,id',
            'notes' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        
        // Determine trialable model
        if ($validated['trialable_type'] === 'customer') {
            $trialable = Customer::findOrFail($validated['trialable_id']);
            $trialableType = Customer::class;
        } else {
            $trialable = CustomerChild::findOrFail($validated['trialable_id']);
            $trialableType = CustomerChild::class;
        }
        
        $class = ClassModel::findOrFail($validated['class_id']);
        
        $registeredSessions = [];
        $skippedSessions = [];

        DB::transaction(function () use ($validated, $trialableType, $class, $user, &$registeredSessions, &$skippedSessions) {
            foreach ($validated['session_ids'] as $sessionId) {
                // Check if already registered
                $exists = TrialStudent::where('trialable_type', $trialableType)
                    ->where('trialable_id', $validated['trialable_id'])
                    ->where('class_lesson_session_id', $sessionId)
                    ->exists();

                if ($exists) {
                    $skippedSessions[] = $sessionId;
                    continue;
                }

                // Create trial registration
                $trial = TrialStudent::create([
                    'trialable_type' => $trialableType,
                    'trialable_id' => $validated['trialable_id'],
                    'class_id' => $validated['class_id'],
                    'class_lesson_session_id' => $sessionId,
                    'registered_by' => $user->id,
                    'registered_at' => now(),
                    'status' => 'registered',
                    'notes' => $validated['notes'] ?? null,
                    'branch_id' => $class->branch_id,
                ]);

                $registeredSessions[] = $trial;
            }
        });

        // Send Zalo notification if registration was successful
        if (count($registeredSessions) > 0) {
            try {
                // Get customer (either directly or via child)
                if ($validated['trialable_type'] === 'customer') {
                    $customer = $trialable;
                } else {
                    $customer = $trialable->customer;
                }

                // Load session details for notification
                $sessionIds = array_map(fn($t) => $t->class_lesson_session_id, $registeredSessions);
                $sessions = ClassLessonSession::whereIn('id', $sessionIds)
                    ->orderBy('scheduled_date')
                    ->orderBy('session_number')
                    ->get();

                // Send notification using first trial record (all have same customer and class)
                $notificationService = app(\App\Services\CustomerZaloNotificationService::class);
                $notificationService->sendTrialClassNotification(
                    $registeredSessions[0],
                    $customer,
                    $sessions->all()
                );
            } catch (\Exception $e) {
                \Log::warning('[TrialStudent] Failed to send Zalo notification', [
                    'trialable_type' => $validated['trialable_type'],
                    'trialable_id' => $validated['trialable_id'],
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the request if notification fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký học thử thành công!',
            'data' => [
                'registered' => count($registeredSessions),
                'skipped' => count($skippedSessions),
                'total' => count($validated['session_ids'])
            ]
        ], 201);
    }

    /**
     * Get trial students for a session
     */
    public function getSessionTrialStudents($sessionId)
    {
        $trialStudents = TrialStudent::with(['trialable', 'registeredBy'])
            ->where('class_lesson_session_id', $sessionId)
            ->active()
            ->orderBy('registered_at')
            ->get();
        
        // Format data for frontend
        $formatted = $trialStudents->map(function ($trial) {
            return [
                'id' => $trial->id,
                'name' => $trial->trial_student_name,
                'type' => $trial->trial_student_type,
                'status' => $trial->status,
                'registered_at' => $trial->registered_at->format('d/m/Y H:i'),
                'registered_by' => $trial->registeredBy->name ?? 'N/A',
                'notes' => $trial->notes,
                'feedback' => $trial->feedback,
                'rating' => $trial->rating,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Update trial student status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:attended,absent,cancelled,converted',
            'feedback' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);
        
        $trial = TrialStudent::findOrFail($id);
        $trial->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $trial->fresh(['trialable', 'session'])
        ]);
    }

    /**
     * Cancel trial registration
     */
    public function cancel($id)
    {
        $trial = TrialStudent::findOrFail($id);
        $trial->update(['status' => 'cancelled']);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đăng ký học thử'
        ]);
    }
}
