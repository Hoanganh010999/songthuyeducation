<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use App\Models\WorkAssignment;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkDashboardController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Build base query based on permissions
        $query = $this->buildPermissionQuery($user);

        // Overview statistics
        $statistics = [
            'total_work_items' => (clone $query)->count(),
            'projects' => (clone $query)->where('type', 'project')->count(),
            'tasks' => (clone $query)->where('type', 'task')->count(),

            'by_status' => [
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'assigned' => (clone $query)->where('status', 'assigned')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'submitted' => (clone $query)->where('status', 'submitted')->count(),
                'revision_requested' => (clone $query)->where('status', 'revision_requested')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            ],

            'by_priority' => [
                'low' => (clone $query)->where('priority', 'low')->count(),
                'medium' => (clone $query)->where('priority', 'medium')->count(),
                'high' => (clone $query)->where('priority', 'high')->count(),
                'urgent' => (clone $query)->where('priority', 'urgent')->count(),
            ],

            'deadlines' => [
                'overdue' => (clone $query)->overdue()->count(),
                'due_today' => (clone $query)->whereDate('due_date', today())->count(),
                'due_this_week' => (clone $query)->whereBetween('due_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'due_this_month' => (clone $query)->whereBetween('due_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count(),
            ],

            'my_assignments' => [
                'as_executor' => WorkAssignment::where('user_id', $user->id)
                    ->where('role', 'executor')
                    ->count(),
                'as_assigner' => WorkAssignment::where('user_id', $user->id)
                    ->where('role', 'assigner')
                    ->count(),
                'as_observer' => WorkAssignment::where('user_id', $user->id)
                    ->where('role', 'observer')
                    ->count(),
                'as_supporter' => WorkAssignment::where('user_id', $user->id)
                    ->where('role', 'supporter')
                    ->count(),
            ],
        ];

        // Recent activities
        $recentWorkItems = (clone $query)
            ->with(['creator', 'assignments.user', 'tags'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'statistics' => $statistics,
            'recent_work_items' => $recentWorkItems,
        ]);
    }

    /**
     * Get detailed statistics
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();
        $query = $this->buildPermissionQuery($user);

        // Time range filter
        $startDate = $request->input('start_date', now()->subMonth()->startOfDay());
        $endDate = $request->input('end_date', now()->endOfDay());

        $query->whereBetween('created_at', [$startDate, $endDate]);

        // Completion rate
        $totalCompleted = (clone $query)->where('status', 'completed')->count();
        $totalItems = (clone $query)->count();
        $completionRate = $totalItems > 0 ? ($totalCompleted / $totalItems) * 100 : 0;

        // Average completion time
        $completedItems = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('start_date')
            ->get();

        $avgCompletionTime = 0;
        if ($completedItems->count() > 0) {
            $totalHours = 0;
            foreach ($completedItems as $item) {
                $start = Carbon::parse($item->start_date);
                $end = Carbon::parse($item->completed_at);
                $totalHours += $start->diffInHours($end);
            }
            $avgCompletionTime = $totalHours / $completedItems->count();
        }

        // On-time completion rate
        $onTimeCompletions = $completedItems->filter(function ($item) {
            if (!$item->due_date || !$item->completed_at) {
                return false;
            }
            return Carbon::parse($item->completed_at)->lte(Carbon::parse($item->due_date));
        })->count();

        $onTimeRate = $completedItems->count() > 0
            ? ($onTimeCompletions / $completedItems->count()) * 100
            : 0;

        // Work items by department
        $byDepartment = [];
        if ($user->can('work_items.view_all') || $user->can('work_items.view_department')) {
            $byDepartment = DB::table('work_items')
                ->join('work_assignments', 'work_items.id', '=', 'work_assignments.work_item_id')
                ->join('departments', 'work_assignments.department_id', '=', 'departments.id')
                ->select('departments.name', DB::raw('count(distinct work_items.id) as count'))
                ->where('work_items.branch_id', $user->primary_branch_id)
                ->whereBetween('work_items.created_at', [$startDate, $endDate])
                ->whereNull('work_items.deleted_at')
                ->groupBy('departments.id', 'departments.name')
                ->get();
        }

        // Work items trend (by day/week/month)
        $groupBy = $request->input('group_by', 'day'); // day, week, month
        $dateFormat = match($groupBy) {
            'week' => '%Y-%U',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $trend = DB::table('work_items')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'completed' then 1 else 0 end) as completed")
            )
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'summary' => [
                'total_items' => $totalItems,
                'completed_items' => $totalCompleted,
                'completion_rate' => round($completionRate, 2),
                'avg_completion_time_hours' => round($avgCompletionTime, 2),
                'on_time_rate' => round($onTimeRate, 2),
            ],
            'by_department' => $byDepartment,
            'trend' => $trend,
        ]);
    }

    /**
     * Get performance metrics for HAY System
     */
    public function performance(Request $request)
    {
        $user = auth()->user();

        // Get user ID to calculate performance for
        $targetUserId = $request->input('user_id', $user->id);

        // Permission check - can only view own performance unless has admin permission
        if ($targetUserId != $user->id && !$user->can('work_items.view_all')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $targetUser = \App\Models\User::findOrFail($targetUserId);

        // Time range
        $startDate = $request->input('start_date', now()->subMonth()->startOfDay());
        $endDate = $request->input('end_date', now()->endOfDay());

        // Get work items as executor
        $workItems = WorkItem::whereHas('assignments', function ($q) use ($targetUserId) {
            $q->where('user_id', $targetUserId)
              ->where('role', 'executor');
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

        // Calculate metrics
        $totalAssigned = $workItems->count();
        $totalCompleted = $workItems->where('status', 'completed')->count();
        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : 0;

        // Quality score (average rating from submissions)
        $qualityScore = DB::table('work_submissions')
            ->whereIn('work_item_id', $workItems->pluck('id'))
            ->where('submitted_by', $targetUserId)
            ->whereNotNull('quality_rating')
            ->avg('quality_rating');

        // Timeliness score (percentage of on-time completions)
        $completedItems = $workItems->where('status', 'completed')->filter(function ($item) {
            return $item->due_date && $item->completed_at;
        });

        $onTimeCompletions = $completedItems->filter(function ($item) {
            return Carbon::parse($item->completed_at)->lte(Carbon::parse($item->due_date));
        })->count();

        $timelinessScore = $completedItems->count() > 0
            ? ($onTimeCompletions / $completedItems->count()) * 100
            : 0;

        // Complexity score (average from metadata)
        $complexityScore = $workItems->filter(function ($item) {
            return isset($item->metadata['complexity']);
        })->avg('metadata.complexity');

        // Calculate overall performance score for HAY System
        $performanceScore = (
            ($completionRate * 0.3) +          // 30% weight on completion
            ($qualityScore * 20 * 0.3) +        // 30% weight on quality (5-star to 100-point)
            ($timelinessScore * 0.2) +          // 20% weight on timeliness
            ($complexityScore * 10 * 0.2)       // 20% weight on complexity (10-point scale)
        );

        return response()->json([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'metrics' => [
                'total_assigned' => $totalAssigned,
                'total_completed' => $totalCompleted,
                'completion_rate' => round($completionRate, 2),
                'quality_score' => round($qualityScore ?? 0, 2),
                'timeliness_score' => round($timelinessScore, 2),
                'complexity_score' => round($complexityScore ?? 0, 2),
                'overall_performance_score' => round($performanceScore, 2),
            ],
            'work_items' => $workItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'title' => $item->title,
                    'status' => $item->status,
                    'priority' => $item->priority,
                    'completion_rate' => $item->calculateCompletionRate(),
                    'is_overdue' => $item->isOverdue(),
                ];
            }),
        ]);
    }

    /**
     * Build permission-based query
     */
    private function buildPermissionQuery($user)
    {
        $query = WorkItem::query();

        if ($user->can('work_items.view_all')) {
            $query->forBranch($user->primary_branch_id);
        } elseif ($user->can('work_items.view_department')) {
            $departmentIds = [$user->department_id];
            if ($user->department && $user->department->head_id == $user->id) {
                $subDepartments = Department::where('parent_id', $user->department_id)
                    ->pluck('id')
                    ->toArray();
                $departmentIds = array_merge($departmentIds, $subDepartments);
            }

            $query->where(function ($q) use ($user, $departmentIds) {
                $q->whereHas('assignments', function ($q) use ($departmentIds) {
                    $q->whereIn('department_id', $departmentIds);
                })->orWhereHas('assignments', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            });
        } else {
            $query->forUser($user->id);
        }

        return $query;
    }
}
