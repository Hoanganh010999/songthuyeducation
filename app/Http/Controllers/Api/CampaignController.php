<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Danh sách campaigns
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $isActive = $request->input('is_active');

        $query = Campaign::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        $campaigns = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    /**
     * Lấy campaigns đang hiệu lực
     */
    public function active()
    {
        $campaigns = Campaign::valid()
            ->byPriority()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    /**
     * Tự động áp dụng campaign tốt nhất cho sản phẩm
     */
    public function autoApply(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);
        $amount = $request->amount;

        // Get all valid campaigns that can be auto-applied
        $campaigns = Campaign::autoApply()
            ->byPriority()
            ->get()
            ->filter(function ($campaign) use ($product, $amount) {
                // Check if campaign applies to this product
                if (!$campaign->canBeAppliedToProduct($product)) {
                    return false;
                }

                // Check min order amount
                if ($campaign->min_order_amount && $amount < $campaign->min_order_amount) {
                    return false;
                }

                return true;
            });

        if ($campaigns->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Không có chiến dịch nào áp dụng'
            ]);
        }

        // Get campaign with best discount
        $bestCampaign = null;
        $bestDiscount = 0;

        foreach ($campaigns as $campaign) {
            $discount = $campaign->calculateDiscount($amount);
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestCampaign = $campaign;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'campaign' => $bestCampaign,
                'discount_amount' => $bestDiscount,
                'final_amount' => $amount - $bestDiscount,
            ]
        ]);
    }

    /**
     * Chi tiết campaign
     */
    public function show(string $id)
    {
        $campaign = Campaign::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $campaign
        ]);
    }

    /**
     * Tạo campaign
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:campaigns,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'applicable_product_ids' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'target_customer_segments' => 'nullable|array',
            'priority' => 'integer|min:0',
            'total_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_auto_apply' => 'boolean',
            'banner_image' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();

        $campaign = Campaign::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo chiến dịch thành công',
            'data' => $campaign
        ], 201);
    }

    /**
     * Cập nhật campaign
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:campaigns,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'applicable_product_ids' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'target_customer_segments' => 'nullable|array',
            'priority' => 'integer|min:0',
            'total_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_auto_apply' => 'boolean',
            'banner_image' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật chiến dịch thành công',
            'data' => $campaign->fresh()
        ]);
    }

    /**
     * Xóa campaign
     */
    public function destroy(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa chiến dịch thành công'
        ]);
    }
}

