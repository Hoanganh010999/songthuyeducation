<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    /**
     * Display a listing of translations
     */
    public function index(Request $request)
    {
        $query = Translation::with('language');

        // Filter by language
        if ($request->has('language_id')) {
            $query->where('language_id', $request->language_id);
        }

        // Filter by group
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%");
            });
        }

        $translations = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }

    /**
     * Get all translation groups
     */
    public function groups()
    {
        $groups = Translation::getGroups();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Store a newly created translation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'required|exists:languages,id',
            'group' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if translation already exists
        $exists = Translation::where('language_id', $request->language_id)
            ->where('group', $request->group)
            ->where('key', $request->key)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Translation already exists for this language, group, and key',
            ], 400);
        }

        $translation = Translation::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Translation created successfully',
            'data' => $translation->load('language'),
        ], 201);
    }

    /**
     * Display the specified translation
     */
    public function show(Translation $translation)
    {
        return response()->json([
            'success' => true,
            'data' => $translation->load('language'),
        ]);
    }

    /**
     * Update the specified translation
     */
    public function update(Request $request, Translation $translation)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'sometimes|required|exists:languages,id',
            'group' => 'sometimes|required|string|max:255',
            'key' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicates if key fields are being updated
        if ($request->has(['language_id', 'group', 'key'])) {
            $exists = Translation::where('language_id', $request->language_id)
                ->where('group', $request->group)
                ->where('key', $request->key)
                ->where('id', '!=', $translation->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translation already exists for this language, group, and key',
                ], 400);
            }
        }

        $translation->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully',
            'data' => $translation->load('language'),
        ]);
    }

    /**
     * Remove the specified translation
     */
    public function destroy(Translation $translation)
    {
        $translation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Translation deleted successfully',
        ]);
    }

    /**
     * Bulk update translations
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'translations.*.id' => 'required|exists:translations,id',
            'translations.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->translations as $translationData) {
            Translation::where('id', $translationData['id'])
                ->update(['value' => $translationData['value']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Translations updated successfully',
        ]);
    }

    /**
     * Sync translations between languages
     * Copy keys from one language to another (useful when adding new language)
     */
    public function syncLanguages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_language_id' => 'required|exists:languages,id',
            'target_language_id' => 'required|exists:languages,id|different:source_language_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sourceTranslations = Translation::where('language_id', $request->source_language_id)->get();
        $created = 0;

        foreach ($sourceTranslations as $sourceTranslation) {
            $exists = Translation::where('language_id', $request->target_language_id)
                ->where('group', $sourceTranslation->group)
                ->where('key', $sourceTranslation->key)
                ->exists();

            if (!$exists) {
                Translation::create([
                    'language_id' => $request->target_language_id,
                    'group' => $sourceTranslation->group,
                    'key' => $sourceTranslation->key,
                    'value' => $sourceTranslation->value, // Copy value as placeholder
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synced {$created} translations",
            'created' => $created,
        ]);
    }
}
