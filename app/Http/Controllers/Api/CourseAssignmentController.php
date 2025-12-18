<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\CourseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseAssignmentController extends Controller
{
    public function index(Request $request, $classId)
    {
        try {
            $assignments = CourseAssignment::with(['creator', 'session'])
                ->where('class_id', $classId)
                ->when($request->status, fn($q, $status) => $q->where('status', $status))
                ->orderBy('due_date', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json($assignments);
        } catch (\Exception $e) {
            Log::error('Error loading assignments', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error loading assignments'], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'nullable|integer|min:0',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $validated['branch_id'] = auth()->user()->primary_branch_id;

            if ($request->hasFile('attachment')) {
                $validated['attachment_path'] = $request->file('attachment')->store('assignments', 'public');
            }

            $assignment = CourseAssignment::create($validated);

            return response()->json([
                'message' => 'Assignment created successfully',
                'data' => $assignment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating assignment', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error creating assignment'], 500);
        }
    }

    public function submitWork(Request $request, $assignmentId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'content' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            $assignment = CourseAssignment::findOrFail($assignmentId);
            
            $validated['assignment_id'] = $assignmentId;
            $validated['submitted_at'] = now();
            $validated['status'] = 'submitted';
            $validated['is_late'] = $assignment->due_date && now()->gt($assignment->due_date);

            if ($request->hasFile('attachment')) {
                $validated['attachment_path'] = $request->file('attachment')->store('submissions', 'public');
            }

            $submission = CourseSubmission::updateOrCreate(
                ['assignment_id' => $assignmentId, 'student_id' => $validated['student_id']],
                $validated
            );

            return response()->json([
                'message' => 'Work submitted successfully',
                'data' => $submission
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error submitting work', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error submitting work'], 500);
        }
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        try {
            $submission = CourseSubmission::findOrFail($submissionId);
            
            $submission->update([
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
                'status' => 'graded',
            ]);

            return response()->json([
                'message' => 'Submission graded successfully',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            Log::error('Error grading submission', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error grading submission'], 500);
        }
    }
}
