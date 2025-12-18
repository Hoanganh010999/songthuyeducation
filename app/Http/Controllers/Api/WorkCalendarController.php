<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use App\Models\Department;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkCalendarController extends Controller
{
    /**
     * Get calendar view of work items
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Date range (default to current month)
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        // Build query based on permissions
        $query = WorkItem::with(['creator', 'assignments.user', 'tags'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Items that start or are due in the range
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('due_date', [$startDate, $endDate])
                  // Or items that span the range
                  ->orWhere(function ($q) use ($startDate, $endDate) {
                      $q->where('start_date', '<=', $startDate)
                        ->where('due_date', '>=', $endDate);
                  });
            });

        // Apply permission filters
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

        // Additional filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $workItems = $query->get();

        // Format for calendar (group by date)
        $calendarData = [];

        foreach ($workItems as $item) {
            $events = [];

            // Add start date event
            if ($item->start_date) {
                $startDate = Carbon::parse($item->start_date)->format('Y-m-d');
                $events[] = [
                    'date' => $startDate,
                    'type' => 'start',
                    'work_item' => $this->formatWorkItem($item),
                ];
            }

            // Add due date event
            if ($item->due_date) {
                $dueDate = Carbon::parse($item->due_date)->format('Y-m-d');
                $events[] = [
                    'date' => $dueDate,
                    'type' => 'due',
                    'work_item' => $this->formatWorkItem($item),
                ];
            }

            foreach ($events as $event) {
                if (!isset($calendarData[$event['date']])) {
                    $calendarData[$event['date']] = [];
                }
                $calendarData[$event['date']][] = $event;
            }
        }

        return response()->json([
            'calendar_data' => $calendarData,
            'summary' => [
                'total_items' => $workItems->count(),
                'start_date' => $request->input('start_date', now()->startOfMonth()->format('Y-m-d')),
                'end_date' => $request->input('end_date', now()->endOfMonth()->format('Y-m-d')),
            ]
        ]);
    }

    /**
     * Get work items for a specific date
     */
    public function byDate(Request $request, $date)
    {
        $user = auth()->user();
        $targetDate = Carbon::parse($date);

        // Build query
        $query = WorkItem::with(['creator', 'assignments.user', 'tags'])
            ->where(function ($q) use ($targetDate) {
                $q->whereDate('start_date', $targetDate)
                  ->orWhereDate('due_date', $targetDate)
                  // Or items that span this date
                  ->orWhere(function ($q) use ($targetDate) {
                      $q->where('start_date', '<=', $targetDate)
                        ->where('due_date', '>=', $targetDate);
                  });
            });

        // Apply permission filters
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

        $workItems = $query->get();

        // Categorize by event type
        $starting = [];
        $due = [];
        $inProgress = [];

        foreach ($workItems as $item) {
            $formattedItem = $this->formatWorkItem($item);

            if ($item->start_date && Carbon::parse($item->start_date)->isSameDay($targetDate)) {
                $starting[] = $formattedItem;
            }

            if ($item->due_date && Carbon::parse($item->due_date)->isSameDay($targetDate)) {
                $due[] = $formattedItem;
            }

            // Items in progress on this date
            if ($item->start_date && $item->due_date) {
                $start = Carbon::parse($item->start_date);
                $end = Carbon::parse($item->due_date);
                if ($targetDate->between($start, $end) &&
                    !$start->isSameDay($targetDate) &&
                    !$end->isSameDay($targetDate)) {
                    $inProgress[] = $formattedItem;
                }
            }
        }

        return response()->json([
            'date' => $date,
            'starting_today' => $starting,
            'due_today' => $due,
            'in_progress' => $inProgress,
            'total' => $workItems->count(),
        ]);
    }

    /**
     * Format work item for calendar display
     */
    private function formatWorkItem($item)
    {
        return [
            'id' => $item->id,
            'code' => $item->code,
            'title' => $item->title,
            'type' => $item->type,
            'status' => $item->status,
            'priority' => $item->priority,
            'start_date' => $item->start_date,
            'due_date' => $item->due_date,
            'is_overdue' => $item->isOverdue(),
            'creator' => $item->creator ? [
                'id' => $item->creator->id,
                'name' => $item->creator->name,
            ] : null,
            'tags' => $item->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ];
            }),
            'assignments' => $item->assignments->map(function ($assignment) {
                return [
                    'role' => $assignment->role,
                    'user' => $assignment->user ? [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                    ] : null,
                ];
            }),
        ];
    }
}
