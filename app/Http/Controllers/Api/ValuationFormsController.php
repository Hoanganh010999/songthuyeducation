<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ValuationForm;
use App\Models\ValuationFormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ValuationFormsController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        $status = $request->input('status');
        
        $query = ValuationForm::with(['creator', 'fields'])
            ->forBranch($branchId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $forms = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $forms
        ]);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array|min:1',
            'fields.*.field_type' => 'required|in:text,checkbox,dropdown',
            'fields.*.field_title' => 'required|string',
            'fields.*.field_description' => 'nullable|string',
            'fields.*.field_config' => 'nullable|array',
            'fields.*.is_required' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $form = ValuationForm::create([
                'branch_id' => $request->branch_id,
                'created_by' => auth()->id(),
                'name' => $request->name,
                'description' => $request->description,
                'status' => 'draft'
            ]);
            
            // Create fields
            foreach ($request->fields as $index => $fieldData) {
                ValuationFormField::create([
                    'valuation_form_id' => $form->id,
                    'field_type' => $fieldData['field_type'],
                    'field_title' => $fieldData['field_title'],
                    'field_description' => $fieldData['field_description'] ?? null,
                    'field_config' => $fieldData['field_config'] ?? null,
                    'is_required' => $fieldData['is_required'] ?? false,
                    'sort_order' => $index + 1
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo form đánh giá thành công',
                'data' => $form->load('fields')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo form: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function show($id)
    {
        $form = ValuationForm::with(['creator', 'fields'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $form
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $form = ValuationForm::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,archived',
            'fields' => 'nullable|array',
            'fields.*.id' => 'nullable|exists:valuation_form_fields,id',
            'fields.*.field_type' => 'required|in:text,checkbox,dropdown',
            'fields.*.field_title' => 'required|string',
            'fields.*.field_description' => 'nullable|string',
            'fields.*.field_config' => 'nullable|array',
            'fields.*.is_required' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $form->update($request->only(['name', 'description', 'status']));
            
            // Update fields if provided
            if ($request->has('fields')) {
                // Get existing field IDs
                $existingFieldIds = $form->fields()->pluck('id')->toArray();
                $updatedFieldIds = collect($request->fields)
                    ->pluck('id')
                    ->filter()
                    ->toArray();
                
                // Delete fields that are not in the update request
                $fieldsToDelete = array_diff($existingFieldIds, $updatedFieldIds);
                ValuationFormField::whereIn('id', $fieldsToDelete)->delete();
                
                // Update or create fields
                foreach ($request->fields as $index => $fieldData) {
                    if (isset($fieldData['id'])) {
                        // Update existing field
                        ValuationFormField::where('id', $fieldData['id'])
                            ->update([
                                'field_type' => $fieldData['field_type'],
                                'field_title' => $fieldData['field_title'],
                                'field_description' => $fieldData['field_description'] ?? null,
                                'field_config' => $fieldData['field_config'] ?? null,
                                'is_required' => $fieldData['is_required'] ?? false,
                                'sort_order' => $index + 1
                            ]);
                    } else {
                        // Create new field
                        ValuationFormField::create([
                            'valuation_form_id' => $form->id,
                            'field_type' => $fieldData['field_type'],
                            'field_title' => $fieldData['field_title'],
                            'field_description' => $fieldData['field_description'] ?? null,
                            'field_config' => $fieldData['field_config'] ?? null,
                            'is_required' => $fieldData['is_required'] ?? false,
                            'sort_order' => $index + 1
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật form đánh giá thành công',
                'data' => $form->load('fields')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật form: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        $form = ValuationForm::findOrFail($id);
        
        // Check if form is being used
        if ($form->lessonPlanSessions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa form đang được sử dụng'
            ], 422);
        }
        
        $form->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa form đánh giá thành công'
        ]);
    }
}
