<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    /**
     * Display a listing of languages (public - for language switcher)
     */
    public function index()
    {
        $languages = Language::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Display all languages (admin only)
     */
    public function all()
    {
        $languages = Language::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Store a newly created language
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:languages,code',
            'flag' => 'nullable|string|max:255',
            'direction' => 'nullable|in:ltr,rtl',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $language = Language::create($validator->validated());

        // Nếu đặt làm mặc định, cập nhật các ngôn ngữ khác
        if ($request->is_default) {
            $language->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Language created successfully',
            'data' => $language,
        ], 201);
    }

    /**
     * Display the specified language
     */
    public function show(Language $language)
    {
        return response()->json([
            'success' => true,
            'data' => $language->load('translations'),
        ]);
    }

    /**
     * Update the specified language
     */
    public function update(Request $request, Language $language)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:10|unique:languages,code,' . $language->id,
            'flag' => 'nullable|string|max:255',
            'direction' => 'nullable|in:ltr,rtl',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $language->update($validator->validated());

        // Nếu đặt làm mặc định, cập nhật các ngôn ngữ khác
        if ($request->is_default) {
            $language->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Language updated successfully',
            'data' => $language,
        ]);
    }

    /**
     * Remove the specified language
     */
    public function destroy(Language $language)
    {
        // Không cho phép xóa ngôn ngữ mặc định
        if ($language->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default language',
            ], 400);
        }

        $language->delete();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted successfully',
        ]);
    }

    /**
     * Set language as default
     */
    public function setDefault(Language $language)
    {
        $language->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Language set as default successfully',
            'data' => $language,
        ]);
    }

    /**
     * Get translations for a specific language and group
     */
    public function getTranslations(Language $language, Request $request)
    {
        $group = $request->query('group');

        if ($group) {
            $translations = $language->getGroupTranslations($group);
        } else {
            $translations = $language->translations()
                ->get()
                ->groupBy('group')
                ->map(function ($items) {
                    return $items->pluck('value', 'key');
                });
        }

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }

    /**
     * Get all translations for frontend (by language code)
     */
    public function getTranslationsByCode(string $code)
    {
        $language = Language::where('code', $code)->where('is_active', true)->first();

        if (!$language) {
            return response()->json([
                'success' => false,
                'message' => 'Language not found',
            ], 404);
        }

        $translations = $language->translations()
            ->get()
            ->groupBy('group')
            ->map(function ($items) {
                return $items->pluck('value', 'key');
            })
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'language' => $language,
                'translations' => $translations,
            ],
        ]);
    }
}
