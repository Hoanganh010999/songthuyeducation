<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Danh sách vouchers
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $isActive = $request->input('is_active');
        $type = $request->input('type');

        $query = Voucher::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $vouchers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * Lấy vouchers có thể áp dụng cho khách hàng
     */
    public function applicableForCustomer(Request $request, string $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $productId = $request->input('product_id');

        $query = Voucher::valid();

        // Filter vouchers that customer can use
        $vouchers = $query->get()->filter(function ($voucher) use ($customer, $productId) {
            if (!$voucher->canBeUsedBy($customer)) {
                return false;
            }

            // If product is specified, check if voucher can be applied to product
            if ($productId) {
                $product = Product::find($productId);
                if ($product && !$voucher->canBeAppliedToProduct($product)) {
                    return false;
                }
            }

            return true;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * Kiểm tra voucher có hợp lệ không
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Mã voucher không tồn tại'
            ], 404);
        }

        $customer = Customer::findOrFail($request->customer_id);
        $product = Product::findOrFail($request->product_id);

        if (!$voucher->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher không còn hiệu lực'
            ], 400);
        }

        if (!$voucher->canBeUsedBy($customer)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể sử dụng voucher này'
            ], 400);
        }

        if (!$voucher->canBeAppliedToProduct($product)) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher không áp dụng cho sản phẩm này'
            ], 400);
        }

        if ($voucher->min_order_amount && $request->amount < $voucher->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu: ' . number_format($voucher->min_order_amount) . 'đ'
            ], 400);
        }

        $discountAmount = $voucher->calculateDiscount($request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Voucher hợp lệ',
            'data' => [
                'voucher' => $voucher,
                'discount_amount' => $discountAmount,
                'final_amount' => $request->amount - $discountAmount,
            ]
        ]);
    }

    /**
     * Chi tiết voucher
     */
    public function show(string $id)
    {
        $voucher = Voucher::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $voucher
        ]);
    }

    /**
     * Tạo voucher
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'applicable_product_ids' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'applicable_customer_ids' => 'nullable|array',
            'is_active' => 'boolean',
            'is_auto_apply' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo voucher thành công',
            'data' => $voucher
        ], 201);
    }

    /**
     * Cập nhật voucher
     */
    public function update(Request $request, string $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'applicable_product_ids' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'applicable_customer_ids' => 'nullable|array',
            'is_active' => 'boolean',
            'is_auto_apply' => 'boolean',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật voucher thành công',
            'data' => $voucher->fresh()
        ]);
    }

    /**
     * Xóa voucher
     */
    public function destroy(string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa voucher thành công'
        ]);
    }
}

