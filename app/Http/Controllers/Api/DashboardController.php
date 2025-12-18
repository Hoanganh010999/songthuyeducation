<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ClassLessonSession;
use App\Models\ClassModel;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\FinancialTransaction;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->get('branch_id');
        $period = $request->get('period', 'month'); // week, month, quarter, year

        // Calculate date ranges
        $now = Carbon::now();
        $startDate = $this->getStartDate($period, $now);
        $previousStartDate = $this->getPreviousStartDate($period, $startDate);

        return response()->json([
            'success' => true,
            'data' => [
                'learning_quality' => $this->getLearningQualityStats($branchId, $startDate, $now),
                'hr_and_teaching' => $this->getHrAndTeachingStats($branchId, $startDate, $now),
                'revenue' => $this->getRevenueStats($branchId, $startDate, $now),
                'overview' => $this->getOverviewStats($branchId),
                'trends' => $this->getTrendStats($branchId, $period),
            ],
        ]);
    }

    /**
     * Get learning quality statistics
     */
    private function getLearningQualityStats($branchId, $startDate, $endDate)
    {
        // Attendance statistics - all time (no date filter for now since no data)
        $attendanceQuery = Attendance::query()
            ->whereHas('session', function ($q) use ($branchId) {
                $q->whereHas('class', function ($q2) use ($branchId) {
                    if ($branchId) {
                        $q2->where('branch_id', $branchId);
                    }
                });
            });

        $totalAttendances = (clone $attendanceQuery)->count();
        $presentCount = (clone $attendanceQuery)->where('status', 'present')->count();
        $absentCount = (clone $attendanceQuery)->where('status', 'absent')->count();
        $lateCount = (clone $attendanceQuery)->where('status', 'late')->count();

        $attendanceRate = $totalAttendances > 0
            ? round(($presentCount + $lateCount) / $totalAttendances * 100, 1)
            : 0;

        // Average scores
        $avgHomeworkScore = (clone $attendanceQuery)
            ->whereNotNull('homework_score')
            ->avg('homework_score');

        $avgParticipationScore = (clone $attendanceQuery)
            ->whereNotNull('participation_score')
            ->avg('participation_score');

        // Session completion - try period first, then all time
        $sessionQueryBase = ClassLessonSession::query()
            ->whereHas('class', function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        $periodSessions = (clone $sessionQueryBase)
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->count();

        // If no sessions in period, show all time
        if ($periodSessions == 0) {
            $sessionQuery = $sessionQueryBase;
        } else {
            $sessionQuery = (clone $sessionQueryBase)->whereBetween('scheduled_date', [$startDate, $endDate]);
        }

        $totalSessions = (clone $sessionQuery)->count();
        $completedSessions = (clone $sessionQuery)->where('status', 'completed')->count();
        $cancelledSessions = (clone $sessionQuery)->where('status', 'cancelled')->count();

        $sessionCompletionRate = $totalSessions > 0
            ? round($completedSessions / $totalSessions * 100, 1)
            : 0;

        return [
            'attendance' => [
                'total' => $totalAttendances,
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'rate' => $attendanceRate,
            ],
            'scores' => [
                'avg_homework' => round($avgHomeworkScore ?? 0, 1),
                'avg_participation' => round($avgParticipationScore ?? 0, 1),
            ],
            'sessions' => [
                'total' => $totalSessions,
                'completed' => $completedSessions,
                'cancelled' => $cancelledSessions,
                'completion_rate' => $sessionCompletionRate,
            ],
        ];
    }

    /**
     * Get HR and teaching activity statistics
     */
    private function getHrAndTeachingStats($branchId, $startDate, $endDate)
    {
        // Staff statistics - employees are users with roles but NOT students/parents
        $staffQuery = User::query()
            ->whereHas('roles') // Must have at least one role
            // Exclude students (users who have active records in students table)
            ->whereNotExists(function($query) {
                $query->select(\DB::raw(1))
                      ->from('students')
                      ->whereColumn('students.user_id', 'users.id')
                      ->where('students.is_active', true);
            })
            // Exclude parents (users who have active records in parents table)
            ->whereNotExists(function($query) {
                $query->select(\DB::raw(1))
                      ->from('parents')
                      ->whereColumn('parents.user_id', 'users.id')
                      ->where('parents.is_active', true);
            });

        if ($branchId) {
            $staffQuery->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        $totalStaff = (clone $staffQuery)->count();
        $activeStaff = (clone $staffQuery)->where('employment_status', 'active')->count();

        // If no active status set, consider all as active
        if ($activeStaff == 0 && $totalStaff > 0) {
            $activeStaff = $totalStaff;
        }

        // Teachers count - users with 'teacher' role
        $teacherQuery = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'teacher'));

        if ($branchId) {
            $teacherQuery->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        $totalTeachers = $teacherQuery->count();

        // If no teachers via roles, try via subjects
        if ($totalTeachers == 0) {
            $totalTeachers = User::whereHas('subjects')->count();
        }

        // Classes statistics
        $classQuery = ClassModel::query();
        if ($branchId) {
            $classQuery->where('branch_id', $branchId);
        }

        $totalClasses = (clone $classQuery)->count();
        $activeClasses = (clone $classQuery)->where('status', 'active')->count();

        // Teaching sessions - try period first, then all time
        $sessionQueryBase = ClassLessonSession::query()
            ->whereHas('class', function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        $periodSessions = (clone $sessionQueryBase)
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->count();

        // If no sessions in period, show all time
        if ($periodSessions == 0) {
            $sessionQuery = $sessionQueryBase;
        } else {
            $sessionQuery = (clone $sessionQueryBase)->whereBetween('scheduled_date', [$startDate, $endDate]);
        }

        $totalSessionsInPeriod = (clone $sessionQuery)->count();
        $completedSessionsInPeriod = (clone $sessionQuery)->where('status', 'completed')->count();

        // Departments
        $departmentQuery = Department::query();
        if ($branchId) {
            $departmentQuery->where('branch_id', $branchId);
        }
        $totalDepartments = $departmentQuery->count();

        // Teaching hours (assuming 2 hours per session)
        $teachingHours = $completedSessionsInPeriod * 2;

        return [
            'staff' => [
                'total' => $totalStaff,
                'active' => $activeStaff,
                'teachers' => $totalTeachers,
            ],
            'organization' => [
                'departments' => $totalDepartments,
                'classes' => $totalClasses,
                'active_classes' => $activeClasses,
            ],
            'teaching_activity' => [
                'total_sessions' => $totalSessionsInPeriod,
                'completed_sessions' => $completedSessionsInPeriod,
                'teaching_hours' => $teachingHours,
            ],
        ];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats($branchId, $startDate, $endDate)
    {
        // Financial transactions - show all time data if period has no data
        $transactionQuery = FinancialTransaction::query()
            ->where('status', 'verified');

        if ($branchId) {
            $transactionQuery->where('branch_id', $branchId);
        }

        // Try period first
        $periodIncome = (clone $transactionQuery)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'income')
            ->sum('amount');

        // If no data in period, show all-time
        if ($periodIncome == 0) {
            $totalIncome = (clone $transactionQuery)
                ->where('transaction_type', 'income')
                ->sum('amount');
            $totalExpense = (clone $transactionQuery)
                ->where('transaction_type', 'expense')
                ->sum('amount');
        } else {
            $totalIncome = $periodIncome;
            $totalExpense = (clone $transactionQuery)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('transaction_type', 'expense')
                ->sum('amount');
        }

        $balance = $totalIncome - $totalExpense;

        // Enrollments - show all time if period has no data
        $enrollmentQuery = Enrollment::query();

        if ($branchId) {
            $enrollmentQuery->where('branch_id', $branchId);
        }

        $periodEnrollments = (clone $enrollmentQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // If no enrollments in period, show all
        if ($periodEnrollments == 0) {
            $totalEnrollments = (clone $enrollmentQuery)->count();
            $enrollmentRevenue = (clone $enrollmentQuery)
                ->whereIn('status', ['active', 'completed', 'paid'])
                ->sum('final_price');
            $paidAmount = (clone $enrollmentQuery)
                ->whereIn('status', ['active', 'completed', 'paid'])
                ->sum('paid_amount');
            $pendingAmount = (clone $enrollmentQuery)
                ->whereIn('status', ['active', 'paid'])
                ->sum('remaining_amount');
        } else {
            $periodQuery = (clone $enrollmentQuery)->whereBetween('created_at', [$startDate, $endDate]);
            $totalEnrollments = $periodEnrollments;
            $enrollmentRevenue = (clone $periodQuery)
                ->whereIn('status', ['active', 'completed', 'paid'])
                ->sum('final_price');
            $paidAmount = (clone $periodQuery)
                ->whereIn('status', ['active', 'completed', 'paid'])
                ->sum('paid_amount');
            $pendingAmount = (clone $periodQuery)
                ->whereIn('status', ['active', 'paid'])
                ->sum('remaining_amount');
        }

        // Monthly breakdown (all time)
        $monthlyRevenue = FinancialTransaction::query()
            ->where('status', 'verified')
            ->where('transaction_type', 'income')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        return [
            'transactions' => [
                'income' => round($totalIncome, 0),
                'expense' => round($totalExpense, 0),
                'balance' => round($balance, 0),
            ],
            'enrollments' => [
                'count' => $totalEnrollments,
                'revenue' => round($enrollmentRevenue, 0),
                'paid' => round($paidAmount, 0),
                'pending' => round($pendingAmount, 0),
            ],
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($branchId)
    {
        // Students - count active students (is_active = true or null means active)
        $studentQuery = Student::query();
        if ($branchId) {
            $studentQuery->where('branch_id', $branchId);
        }
        // Count all students (soft deletes already excluded by Laravel)
        $totalStudents = (clone $studentQuery)->count();
        // Also get active count
        $activeStudents = (clone $studentQuery)->where('is_active', true)->count();
        // If no is_active set, use total
        if ($activeStudents == 0 && $totalStudents > 0) {
            $activeStudents = $totalStudents;
        }

        // Customers
        $customerQuery = Customer::query();
        if ($branchId) {
            $customerQuery->where('branch_id', $branchId);
        }
        $totalCustomers = (clone $customerQuery)->count();
        $newCustomers = (clone $customerQuery)
            ->where('stage', 'lead')
            ->count();
        $closedCustomers = (clone $customerQuery)
            ->where('stage', 'closed_won')
            ->count();

        // Parents
        $parentQuery = ParentModel::query();
        if ($branchId) {
            $parentQuery->where('branch_id', $branchId);
        }
        $totalParents = $parentQuery->count();

        // Branches - manual count since relationships may not exist
        $branches = Branch::all()->map(function($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'students' => Student::where('branch_id', $b->id)->count(),
                'customers' => Customer::where('branch_id', $b->id)->count(),
            ];
        });

        return [
            'students' => $activeStudents, // Use active students count
            'customers' => [
                'total' => $totalCustomers,
                'leads' => $newCustomers,
                'closed' => $closedCustomers,
            ],
            'parents' => $totalParents,
            'branches' => $branches,
        ];
    }

    /**
     * Get trend statistics
     */
    private function getTrendStats($branchId, $period)
    {
        $now = Carbon::now();
        $labels = [];
        $enrollments = [];
        $revenue = [];
        $sessions = [];

        // Generate data for last 6 periods
        for ($i = 5; $i >= 0; $i--) {
            if ($period === 'week') {
                $date = $now->copy()->subWeeks($i);
                $startDate = $date->copy()->startOfWeek();
                $endDate = $date->copy()->endOfWeek();
                $labels[] = 'Tuần ' . $date->weekOfYear;
            } elseif ($period === 'month') {
                $date = $now->copy()->subMonths($i);
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();
                $labels[] = 'T' . $date->month;
            } elseif ($period === 'quarter') {
                $date = $now->copy()->subQuarters($i);
                $startDate = $date->copy()->startOfQuarter();
                $endDate = $date->copy()->endOfQuarter();
                $labels[] = 'Q' . $date->quarter . '/' . $date->year;
            } else {
                $date = $now->copy()->subYears($i);
                $startDate = $date->copy()->startOfYear();
                $endDate = $date->copy()->endOfYear();
                $labels[] = $date->year;
            }

            // Enrollments count
            $enrollmentCount = Enrollment::query()
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $enrollments[] = $enrollmentCount;

            // Revenue
            $revenueAmount = FinancialTransaction::query()
                ->where('status', 'verified')
                ->where('transaction_type', 'income')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount');
            $revenue[] = round($revenueAmount, 0);

            // Completed sessions
            $sessionCount = ClassLessonSession::query()
                ->where('status', 'completed')
                ->whereHas('class', function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                })
                ->whereBetween('scheduled_date', [$startDate, $endDate])
                ->count();
            $sessions[] = $sessionCount;
        }

        return [
            'labels' => $labels,
            'enrollments' => $enrollments,
            'revenue' => $revenue,
            'sessions' => $sessions,
        ];
    }

    /**
     * Get start date based on period
     */
    private function getStartDate($period, $now)
    {
        return match ($period) {
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
            'quarter' => $now->copy()->startOfQuarter(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };
    }

    /**
     * Get previous period start date
     */
    private function getPreviousStartDate($period, $startDate)
    {
        return match ($period) {
            'week' => $startDate->copy()->subWeek(),
            'month' => $startDate->copy()->subMonth(),
            'quarter' => $startDate->copy()->subQuarter(),
            'year' => $startDate->copy()->subYear(),
            default => $startDate->copy()->subMonth(),
        };
    }
}
