<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Danh sách sản phẩm
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $type = $request->input('type');
        $category = $request->input('category');
        $isActive = $request->input('is_active');
        $isFeatured = $request->input('is_featured');

        $query = Product::query();

        if ($search) {
            $query->search($search);
        }

        if ($type) {
            $query->byType($type);
        }

        if ($category) {
            $query->byCategory($category);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        if ($isFeatured) {
            $query->featured();
        }

        $products = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Lấy sản phẩm nổi bật
     */
    public function featured()
    {
        $products = Product::active()
            ->featured()
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Chi tiết sản phẩm
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Tạo sản phẩm mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:course,package,material,service',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'duration_months' => 'nullable|integer|min:1',
            'total_sessions' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:100',
            'level' => 'nullable|string|max:100',
            'target_ages' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'allow_trial' => 'boolean',
            'image' => 'nullable|string',
            'gallery' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm thành công',
            'data' => $product
        ], 201);
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $id,
            'description' => 'nullable|string',
            'type' => 'required|in:course,package,material,service',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'duration_months' => 'nullable|integer|min:1',
            'total_sessions' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:100',
            'level' => 'nullable|string|max:100',
            'target_ages' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'allow_trial' => 'boolean',
            'image' => 'nullable|string',
            'gallery' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $validated['updated_by'] = auth()->id();

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công',
            'data' => $product->fresh()
        ]);
    }

    /**
     * Xóa sản phẩm (soft delete)
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sản phẩm thành công'
        ]);
    }

    /**
     * Lấy danh sách categories
     */
    public function categories()
    {
        $categories = Product::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}

