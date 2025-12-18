<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SystemSettingsController extends Controller
{
    /**
     * Get all settings or by group
     */
    public function index(Request $request)
    {
        $group = $request->input('group');
        
        $query = DB::table('settings');
        
        if ($group) {
            $query->where('group', $group);
        }
        
        $settings = $query->get();
        
        // Format as key-value pairs
        $formatted = [];
        foreach ($settings as $setting) {
            $formatted[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
                'description' => $setting->description
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Get a single setting by key
     */
    public function show($key)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * Update or create a setting
     */
    public function update(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'nullable|in:string,number,boolean,json',
            'group' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $setting = DB::table('settings')->where('key', $key)->first();
        
        if ($setting) {
            // Update existing
            DB::table('settings')
                ->where('key', $key)
                ->update([
                    'value' => $request->value,
                    'type' => $request->type ?? $setting->type,
                    'group' => $request->group ?? $setting->group,
                    'description' => $request->description ?? $setting->description,
                    'updated_at' => now()
                ]);
        } else {
            // Create new
            DB::table('settings')->insert([
                'key' => $key,
                'value' => $request->value,
                'type' => $request->type ?? 'string',
                'group' => $request->group ?? 'general',
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->settings as $settingData) {
            $setting = DB::table('settings')->where('key', $settingData['key'])->first();
            
            if ($setting) {
                // Update existing setting
                DB::table('settings')
                    ->where('key', $settingData['key'])
                    ->update([
                        'value' => $settingData['value'],
                        'updated_at' => now()
                    ]);
            } else {
                // Create new setting if it doesn't exist
                DB::table('settings')->insert([
                    'key' => $settingData['key'],
                    'value' => $settingData['value'],
                    'type' => 'string',
                    'group' => 'general',
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Delete a setting
     */
    public function destroy($key)
    {
        $deleted = DB::table('settings')->where('key', $key)->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully'
        ]);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favicon' => 'required|file|mimes:ico,png,svg|max:1024' // max 1MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('favicon');
            $fileName = 'favicon-' . time() . '.' . $file->getClientOriginalExtension();

            // Store in public/uploads/settings directory
            $path = $file->storeAs('uploads/settings', $fileName, 'public');

            // Generate URL
            $url = asset('storage/' . $path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'message' => 'Favicon uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload favicon: ' . $e->getMessage()
            ], 500);
        }
    }
}
