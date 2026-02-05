<?php

namespace App\Http\Controllers;

use App\Models\TranslationKey;
use App\Models\TranslationValue;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Admin Translation Controller
 */
class AdminTranslationController extends Controller
{
    public function __construct(
        private TranslationService $translationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = TranslationKey::with('values');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        $translations = $query->orderBy('key')->paginate($request->get('limit', 15));

        return $this->paginated($translations);
    }

    public function show(int $id): JsonResponse
    {
        $translationKey = TranslationKey::with('values')->findOrFail($id);
        return $this->success($translationKey);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:translation_keys,key',
            'default_value' => 'required|string',
            'translations' => 'sometimes|array',
            'translations.*.locale' => 'required|string|in:en,da',
            'translations.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $translationKey = $this->translationService->createOrUpdateKey(
            $request->key,
            $request->default_value
        );

        // Create translation values if provided
        if ($request->has('translations')) {
            foreach ($request->translations as $translation) {
                $this->translationService->createOrUpdateValue(
                    $translationKey->id,
                    $translation['locale'],
                    $translation['value']
                );
            }
        }

        $translationKey->load('values');
        return $this->created($translationKey);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $translationKey = TranslationKey::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('translation_keys', 'key')->ignore($id)],
            'default_value' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        if ($request->has('key')) {
            $translationKey->key = $request->key;
        }
        if ($request->has('default_value')) {
            $translationKey->default_value = $request->default_value;
        }
        $translationKey->save();

        $this->translationService->invalidateCache($translationKey->key);

        $translationKey->load('values');
        return $this->success($translationKey);
    }

    public function updateValue(Request $request, int $id, string $locale): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        if (!in_array($locale, ['en', 'da'])) {
            return $this->error('Invalid locale. Supported locales: en, da');
        }

        $translationKey = TranslationKey::findOrFail($id);
        $this->translationService->createOrUpdateValue(
            $translationKey->id,
            $locale,
            $request->value
        );

        $translationKey->load('values');
        return $this->success($translationKey);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->translationService->deleteKey($id);
        return $this->noContent();
    }

    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $fileType = $file->getClientOriginalExtension();

        try {
            $results = $this->translationService->importFromFile($filePath, $fileType);

            return $this->success([
                'message' => 'Import completed',
                'created' => $results['created'],
                'updated' => $results['updated'],
                'errors' => $results['errors'],
            ]);
        } catch (\Exception $e) {
            return $this->error('Import failed: ' . $e->getMessage());
        }
    }

    public function export(Request $request): JsonResponse
    {
        $data = $this->translationService->exportToArray();

        return $this->success([
            'data' => $data,
            'format' => 'array', // Frontend can convert to CSV/Excel
        ]);
    }

    public function locales(): JsonResponse
    {
        $locales = $this->translationService->getSupportedLocales();
        return $this->success($locales);
    }
}
