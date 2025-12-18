<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Customer;
use App\Models\CustomerChild;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\Campaign;
use App\Models\Wallet;
use App\Models\VoucherUsage;
use App\Services\TransactionService;
use App\Services\UserCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    protected $transactionService;
    protected $userCreationService;

    public function __construct(TransactionService $transactionService, UserCreationService $userCreationService)
    {
        $this->transactionService = $transactionService;
        $this->userCreationService = $userCreationService;
    }

    /**
     * Danh sách enrollments
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');
        $branchId = $request->input('branch_id');

        $query = Enrollment::with([
            'customer:id,code,name,phone,email',
            'student',
            'product:id,code,name,type',
            'voucher:id,code,name',
            'campaign:id,code,name',
            'branch:id,code,name',
        ])
        ->accessibleBy($request->user(), $branchId); // ← Apply permission scope

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->byStatus($status);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $enrollments = $query->latest()->paginate($perPage);

        // Add has_approved_income flag for each enrollment
        $enrollments->getCollection()->transform(function ($enrollment) {
            $enrollment->has_approved_income = $enrollment->hasApprovedIncomeReport();
            return $enrollment;
        });

        return response()->json([
            'success' => true,
            'data' => $enrollments
        ]);
    }

    /**
     * Chi tiết enrollment
     */
    public function show(string $id)
    {
        $enrollment = Enrollment::with([
            'customer',
            'student',
            'product',
            'voucher',
            'campaign',
            'branch',
            'assignedUser',
            'walletTransactions',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $enrollment
        ]);
    }

    /**
     * CHỐT ĐƠN - Tạo enrollment mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'student_type' => 'required|in:App\Models\Customer,App\Models\CustomerChild',
            'student_id' => 'required',
            'product_id' => 'required|exists:products,id',
            'voucher_id' => 'nullable|exists:vouchers,id',
            'voucher_code' => 'nullable|string',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($validated['customer_id']);
            $product = Product::findOrFail($validated['product_id']);
            $user = auth()->user();

            // Validate student exists
            if ($validated['student_type'] === 'App\Models\Customer') {
                $student = Customer::findOrFail($validated['student_id']);
            } else {
                $student = CustomerChild::findOrFail($validated['student_id']);
            }

            // Determine branch
            $branchId = $user->isSuperAdmin() 
                ? $customer->branch_id 
                : $user->getPrimaryBranch()->id;

            // Calculate prices
            $originalPrice = $product->current_price;
            $discountAmount = 0;
            $voucherId = null;
            $campaignId = null;
            $voucherCode = null;

            \Log::info('💰 ENROLLMENT: Starting price calculation', [
                'original_price' => $originalPrice,
                'voucher_id' => $validated['voucher_id'] ?? null,
                'voucher_code' => $validated['voucher_code'] ?? null,
                'campaign_id' => $validated['campaign_id'] ?? null,
            ]);

            // 1. Try to apply voucher if provided (by ID or code)
            $voucher = null;
            if (!empty($validated['voucher_id'])) {
                $voucher = Voucher::find($validated['voucher_id']);
                \Log::info('🎫 ENROLLMENT: Voucher provided by ID', [
                    'voucher_id' => $validated['voucher_id'],
                    'voucher_found' => $voucher ? 'yes' : 'no',
                ]);
            } elseif (!empty($validated['voucher_code'])) {
                $voucher = Voucher::where('code', $validated['voucher_code'])->first();
                \Log::info('🎫 ENROLLMENT: Voucher provided by code', [
                    'voucher_code' => $validated['voucher_code'],
                    'voucher_found' => $voucher ? 'yes' : 'no',
                ]);
            }
            
            if ($voucher) {
                \Log::info('✅ ENROLLMENT: Voucher found, validating', [
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->code,
                    'is_valid' => $voucher->isValid(),
                    'can_be_used_by_customer' => $voucher->canBeUsedBy($customer),
                    'can_be_applied_to_product' => $voucher->canBeAppliedToProduct($product),
                ]);
                
                if ($voucher->isValid() && 
                    $voucher->canBeUsedBy($customer) && 
                    $voucher->canBeAppliedToProduct($product)) {
                    
                    $voucherDiscount = $voucher->calculateDiscount($originalPrice);
                    $discountAmount = $voucherDiscount;
                    $voucherId = $voucher->id;
                    $voucherCode = $voucher->code;
                    
                    \Log::info('💵 ENROLLMENT: Voucher discount calculated', [
                        'discount_amount' => $discountAmount,
                    ]);
                } else {
                    \Log::warning('⚠️ ENROLLMENT: Voucher validation failed', [
                        'voucher_id' => $voucher->id,
                    ]);
                }
            }

            // 2. Apply campaign if provided (no auto-apply)
            if (!empty($validated['campaign_id'])) {
                $campaign = Campaign::find($validated['campaign_id']);
                
                \Log::info('🎉 ENROLLMENT: Campaign provided', [
                    'campaign_id' => $validated['campaign_id'],
                    'campaign_found' => $campaign ? 'yes' : 'no',
                ]);
                
                if ($campaign && $campaign->isValid() && $campaign->canBeAppliedToProduct($product)) {
                    $campaignDiscount = $campaign->calculateDiscount($originalPrice);
                    
                    // If no voucher, or campaign gives better discount
                    if (!$voucherId || $campaignDiscount > $discountAmount) {
                        $discountAmount = $campaignDiscount;
                        $campaignId = $campaign->id;
                        $voucherId = null; // Campaign takes priority
                        $voucherCode = null;
                        
                        \Log::info('💵 ENROLLMENT: Campaign discount applied', [
                            'discount_amount' => $discountAmount,
                        ]);
                    }
                }
            }

            $finalPrice = $originalPrice - $discountAmount;
            
            \Log::info('💰 ENROLLMENT: Final pricing', [
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'voucher_id' => $voucherId,
                'campaign_id' => $campaignId,
            ]);

            // Create enrollment
            $enrollment = Enrollment::create([
                'customer_id' => $customer->id,
                'student_type' => $validated['student_type'],
                'student_id' => $validated['student_id'],
                'product_id' => $product->id,
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'paid_amount' => 0,
                'remaining_amount' => $finalPrice,
                'voucher_id' => $voucherId,
                'campaign_id' => $campaignId,
                'voucher_code' => $voucherCode,
                'total_sessions' => $product->total_sessions,
                'attended_sessions' => 0,
                'remaining_sessions' => $product->total_sessions,
                'price_per_session' => $product->price_per_session,
                'status' => Enrollment::STATUS_PENDING,
                'branch_id' => $branchId,
                'assigned_to' => $user->id,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Record voucher usage
            if ($voucherId) {
                try {
                    \Log::info('💳 ENROLLMENT: Creating voucher usage', [
                        'voucher_id' => $voucherId,
                        'customer_id' => $customer->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    
                    $voucherUsage = VoucherUsage::create([
                        'voucher_id' => $voucherId,
                        'customer_id' => $customer->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    
                    \Log::info('✅ ENROLLMENT: Voucher usage created', [
                        'voucher_usage_id' => $voucherUsage->id,
                    ]);
                    
                    $voucher = Voucher::find($voucherId);
                    $usageBefore = $voucher->usage_count;
                    $voucher->incrementUsage();
                    $usageAfter = $voucher->fresh()->usage_count;
                    
                    \Log::info('📊 ENROLLMENT: Voucher usage incremented', [
                        'voucher_id' => $voucherId,
                        'usage_before' => $usageBefore,
                        'usage_after' => $usageAfter,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('❌ ENROLLMENT: Failed to create voucher usage', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'voucher_id' => $voucherId,
                    ]);
                    // Continue without failing the enrollment
                }
            } else {
                \Log::info('ℹ️ ENROLLMENT: No voucher applied', [
                    'discount_amount' => $discountAmount,
                    'campaign_id' => $campaignId,
                ]);
            }

            // Increment campaign usage
            if ($campaignId) {
                Campaign::find($campaignId)->incrementUsage();
            }

            // Tạo IncomeReport ngay (chờ duyệt)
            $this->transactionService->createIncomeFromEnrollment(
                $enrollment,
                $finalPrice,
                'pending' // Payment method tạm thời, sẽ update sau khi confirm payment
            );

            // ⚠️ NOTE: User & Wallet sẽ được tạo khi IncomeReport được verify (bước 3)
            // Không tạo ngay để tránh tạo dữ liệu không cần thiết nếu enrollment bị hủy

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Chốt đơn thành công. Phiếu báo thu đã được tạo và đang chờ duyệt.',
                'data' => $enrollment->load([
                    'customer',
                    'student',
                    'product',
                    'voucher',
                    'campaign',
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * XÁC NHẬN THANH TOÁN - Chuyển tiền vào ví
     */
    public function confirmPayment(Request $request, string $id)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,card,wallet',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $enrollment = Enrollment::findOrFail($id);

            if ($enrollment->status === Enrollment::STATUS_PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng đã được thanh toán'
                ], 400);
            }

            $amount = $validated['amount'];

            // Get or create wallet for student
            $student = $enrollment->student;
            $wallet = $student->wallet;
            
            if (!$wallet) {
                $wallet = Wallet::create([
                    'owner_id' => $student->id,
                    'owner_type' => get_class($student),
                    'branch_id' => $enrollment->branch_id,
                    'is_active' => true,
                ]);
            }

            // Deposit money into wallet
            $walletTransaction = $wallet->deposit(
                $amount,
                $enrollment,
                "Nạp tiền từ đơn hàng {$enrollment->code}"
            );

            // Update enrollment payment status
            $enrollment->update([
                'paid_amount' => $enrollment->paid_amount + $amount,
                'remaining_amount' => $enrollment->final_price - ($enrollment->paid_amount + $amount),
                'status' => ($enrollment->paid_amount + $amount >= $enrollment->final_price) 
                    ? Enrollment::STATUS_PAID 
                    : $enrollment->status, // Giữ nguyên status hiện tại nếu chưa đủ
            ]);

            // If fully paid, activate enrollment
            if ($enrollment->status === Enrollment::STATUS_PAID) {
                $enrollment->activate();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xác nhận thanh toán thành công',
                'data' => [
                    'enrollment' => $enrollment->fresh(),
                    'wallet' => $wallet->fresh(),
                    'transaction' => $walletTransaction,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa enrollment (chỉ khi chưa có income report approved)
     */
    public function destroy(string $id)
    {
        try {
            $enrollment = Enrollment::findOrFail($id);

            // ⚠️ BẢO VỆ DỮ LIỆU: Không cho xóa nếu đã được approve
            if (in_array($enrollment->status, ['approved', 'paid', 'active', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa enrollment đã được duyệt hoặc đã thanh toán'
                ], 400);
            }

            // Kiểm tra xem có IncomeReport approved chưa (double-check)
            if ($enrollment->hasApprovedIncomeReport()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa enrollment đã có phiếu báo thu được duyệt'
                ], 400);
            }

            // Soft delete
            $enrollment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa enrollment thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy enrollment
     */
    public function cancel(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $enrollment = Enrollment::findOrFail($id);

        // ⚠️ BẢO VỆ DỮ LIỆU: Không cho hủy nếu đã verified (đã có user/wallet/giao dịch tài chính)
        if (in_array($enrollment->status, ['paid', 'active', 'completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng đã thanh toán hoặc đã hoàn thành'
            ], 400);
        }

        // Cho phép hủy nếu status = pending hoặc approved (chưa verify)
        $enrollment->cancel($validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Hủy đơn hàng thành công',
            'data' => $enrollment->fresh()
        ]);
    }

    /**
     * Thống kê enrollments
     */
    public function statistics(Request $request)
    {
        $query = Enrollment::query();

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'pending' => $query->clone()->where('status', Enrollment::STATUS_PENDING)->count(),
                'paid' => $query->clone()->where('status', Enrollment::STATUS_PAID)->count(),
                'active' => $query->clone()->where('status', Enrollment::STATUS_ACTIVE)->count(),
                'completed' => $query->clone()->where('status', Enrollment::STATUS_COMPLETED)->count(),
                'cancelled' => $query->clone()->where('status', Enrollment::STATUS_CANCELLED)->count(),
            ],
            'total_revenue' => $query->clone()->whereIn('status', [Enrollment::STATUS_PAID, Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])->sum('final_price'),
            'pending_revenue' => $query->clone()->where('status', Enrollment::STATUS_PENDING)->sum('remaining_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

