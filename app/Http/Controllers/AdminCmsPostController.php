<?php

namespace App\Http\Controllers;

use App\Constants\CmsPostStatus;
use App\Models\CmsPost;
use App\Models\CmsPostCategory;
use App\Services\Cms\CmsTemplateCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCmsPostController extends Controller
{
    public function categories(): JsonResponse
    {
        return $this->success(CmsPostCategory::orderBy('sort_order')->orderBy('name')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_post_categories,slug',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        return $this->created(CmsPostCategory::create($data));
    }

    public function index(Request $request): JsonResponse
    {
        $query = CmsPost::with(['category', 'author:id,name', 'featuredMedia'])->orderByDesc('updated_at');
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $this->success($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $post = CmsPost::with(['category', 'author', 'featuredMedia', 'versions.creator:id,name'])->findOrFail($id);

        return $this->success($post);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePost($request);
        $post = CmsPost::create($data);
        $post->recordVersion($request->user()?->id);

        return $this->created($post->load(['category', 'featuredMedia']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = CmsPost::findOrFail($id);
        $post->update($this->validatePost($request, $post->id));

        return $this->success($post->fresh(['category', 'featuredMedia', 'versions']));
    }

    public function destroy(int $id): JsonResponse
    {
        CmsPost::findOrFail($id)->delete();

        return $this->noContent();
    }

    public function restoreVersion(Request $request, int $id, int $versionId): JsonResponse
    {
        $post = CmsPost::findOrFail($id);
        $post->restoreVersion($versionId, $request->user()?->id);

        return $this->success($post->fresh(['versions']));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePost(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:cms_post_categories,id',
            'featured_media_id' => 'nullable|exists:cms_media,id',
            'slug' => ['required', 'string', 'max:255', Rule::unique('cms_posts', 'slug')->ignore($id)],
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content_html' => 'nullable|string',
            'layout' => ['nullable', 'string', Rule::in(CmsTemplateCatalog::blogLayouts())],
            'style' => ['nullable', 'string', Rule::in(CmsTemplateCatalog::styles())],
            'sections' => 'nullable|array',
            'sections.*.id' => 'nullable|string|max:64',
            'sections.*.type' => 'required_with:sections|string|max:64',
            'sections.*.variant' => 'nullable|string|max:64',
            'sections.*.content' => 'nullable|array',
            'status' => ['required', Rule::in(CmsPostStatus::values())],
            'published_at' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:500',
            'robots' => 'nullable|string|max:100',
            'canonical_url' => 'nullable|string|max:500',
        ]);

        $data['author_user_id'] = $request->user()?->id;
        $data['layout'] = $data['layout'] ?? 'classic';
        $data['style'] = $data['style'] ?? 'brand';

        $normalizedSections = [];
        foreach ($data['sections'] ?? [] as $section) {
            if (is_array($section)) {
                $normalizedSections[] = CmsTemplateCatalog::normalizeBlogSection($section);
            }
        }
        $data['sections'] = $normalizedSections;

        if (array_key_exists('content_html', $data) && is_string($data['content_html'])) {
            $data['content_html'] = app(\App\Services\HtmlSanitizer::class)->purify($data['content_html']);
        }

        if ($data['status'] === CmsPostStatus::PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
