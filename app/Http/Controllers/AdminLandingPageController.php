<?php

namespace App\Http\Controllers;

use App\Constants\CmsPostStatus;
use App\Models\LandingPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminLandingPageController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(LandingPage::orderByDesc('updated_at')->get());
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(
            LandingPage::with('versions.creator:id,name')->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePage($request);
        $page = LandingPage::create($data);
        $page->recordVersion($request->user()?->id);

        return $this->created($page);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = LandingPage::findOrFail($id);
        $page->update($this->validatePage($request, $page->id));

        return $this->success($page->fresh('versions'));
    }

    public function destroy(int $id): JsonResponse
    {
        LandingPage::findOrFail($id)->delete();

        return $this->noContent();
    }

    public function restoreVersion(Request $request, int $id, int $versionId): JsonResponse
    {
        $page = LandingPage::findOrFail($id);
        $page->restoreVersion($versionId, $request->user()?->id);

        return $this->success($page->fresh('versions'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePage(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('landing_pages', 'slug')->ignore($id)],
            'title' => 'required|string|max:255',
            'blocks' => 'nullable|array',
            'status' => ['required', Rule::in(CmsPostStatus::values())],
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|string|max:500',
            'robots' => 'nullable|string|max:100',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:500',
        ]);

        if ($data['status'] === CmsPostStatus::PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
