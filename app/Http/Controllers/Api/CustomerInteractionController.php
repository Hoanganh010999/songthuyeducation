<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerInteractionController extends Controller
{
    /**
     * Lấy danh sách interactions của customer
     */
    public function index($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        $interactions = CustomerInteraction::with([
            'user:id,name',
            'interactionType:id,name,code,icon,color',
            'interactionResult:id,name,code,icon,color'
        ])
        ->forCustomer($customerId)
        ->latest()
        ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $interactions
        ]);
    }

    /**
     * Tạo interaction mới
     */
    public function store(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        $validated = $request->validate([
            'interaction_type_id' => 'required|exists:customer_interaction_types,id',
            'interaction_result_id' => 'required|exists:customer_interaction_results,id',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
            'next_follow_up' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);
        
        $validated['customer_id'] = $customerId;
        $validated['user_id'] = Auth::id();
        
        $interaction = CustomerInteraction::create($validated);
        
        // Load relationships
        $interaction->load([
            'user:id,name',
            'interactionType:id,name,code,icon,color',
            'interactionResult:id,name,code,icon,color'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Thêm lịch sử tương tác thành công',
            'data' => $interaction
        ], 201);
    }

    /**
     * Cập nhật interaction
     */
    public function update(Request $request, $customerId, $interactionId)
    {
        $interaction = CustomerInteraction::where('customer_id', $customerId)
            ->where('id', $interactionId)
            ->firstOrFail();
        
        $validated = $request->validate([
            'interaction_type_id' => 'required|exists:customer_interaction_types,id',
            'interaction_result_id' => 'required|exists:customer_interaction_results,id',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
            'next_follow_up' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);
        
        $interaction->update($validated);
        
        // Load relationships
        $interaction->load([
            'user:id,name',
            'interactionType:id,name,code,icon,color',
            'interactionResult:id,name,code,icon,color'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật lịch sử tương tác thành công',
            'data' => $interaction
        ]);
    }

    /**
     * Xóa interaction
     */
    public function destroy($customerId, $interactionId)
    {
        $interaction = CustomerInteraction::where('customer_id', $customerId)
            ->where('id', $interactionId)
            ->firstOrFail();
        
        $interaction->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Xóa lịch sử tương tác thành công'
        ]);
    }
}
