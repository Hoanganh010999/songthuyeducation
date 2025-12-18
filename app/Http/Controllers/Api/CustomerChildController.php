<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerChild;
use Illuminate\Http\Request;

class CustomerChildController extends Controller
{
    public function index($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $children = $customer->children()->orderBy('date_of_birth', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $children,
        ]);
    }

    public function store(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'school' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:100',
            'interests' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $validated['customer_id'] = $customerId;
        $child = CustomerChild::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm con thành công',
            'data' => $child,
        ], 201);
    }

    public function update(Request $request, $customerId, $childId)
    {
        $child = CustomerChild::where('customer_id', $customerId)
            ->where('id', $childId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'school' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:100',
            'interests' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $child->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin con thành công',
            'data' => $child,
        ]);
    }

    public function destroy($customerId, $childId)
    {
        $child = CustomerChild::where('customer_id', $customerId)
            ->where('id', $childId)
            ->firstOrFail();

        $child->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thông tin con thành công',
        ]);
    }
}
