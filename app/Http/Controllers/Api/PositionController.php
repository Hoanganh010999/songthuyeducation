<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::with('roles');
        
        // Filter by branch
        $branchId = $request->input('branch_id');
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        
        $positions = $query->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json($positions);
    }
    
    public function store(Request $request)
    {
        \Log::info('[Position] Store request', [
            'data' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:positions,code,NULL,id,branch_id,' . $request->branch_id,
            'description' => 'nullable|string',
            'level' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            \Log::warning('[Position] Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all(),
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Auto-generate code if not provided
        $code = $request->code ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $request->name), 0, 10));
        
        $position = Position::create([
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'code' => $code,
            'description' => $request->description,
            'level' => $request->level ?? 0,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);
        
        // Attach roles
        if ($request->has('role_ids')) {
            $position->roles()->sync($request->role_ids);
        }
        
        return response()->json($position->load('roles'), 201);
    }
    
    public function show(Position $position)
    {
        return response()->json($position->load('roles'));
    }
    
    public function update(Request $request, Position $position)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:positions,code,' . $position->id . ',id,branch_id,' . $position->branch_id,
            'description' => 'nullable|string',
            'level' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $position->update($request->only([
            'name', 'code', 'description', 'level', 'sort_order', 'is_active'
        ]));
        
        // Sync roles
        if ($request->has('role_ids')) {
            $position->roles()->sync($request->role_ids);
        }
        
        return response()->json($position->load('roles'));
    }
    
    public function destroy(Position $position)
    {
        // Check if position is being used
        if ($position->users()->count() > 0) {
            return response()->json([
                'message' => 'Không thể xóa Job Title đang được sử dụng'
            ], 400);
        }
        
        $position->delete();
        
        return response()->json(['message' => 'Đã xóa Job Title thành công']);
    }
}
