<?php

namespace App\Http\Controllers;

use App\Models\CmsMedia;
use App\Services\Cms\CmsMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCmsMediaController extends Controller
{
    public function __construct(
        private CmsMediaService $mediaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CmsMedia::with('uploader:id,name')->orderByDesc('created_at');
        if ($search = $request->get('search')) {
            $query->where('filename', 'like', "%{$search}%");
        }

        $items = $query->paginate($request->integer('limit', 24));

        return $this->paginated($items->through(function (CmsMedia $media) {
            return array_merge($media->toArray(), ['url' => $media->url()]);
        }));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,webp,gif,svg,pdf|max:20480',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->user()?->id,
            $request->get('alt_text')
        );

        return $this->created(array_merge($media->toArray(), ['url' => $media->url()]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $media = CmsMedia::findOrFail($id);
        $data = $request->validate(['alt_text' => 'nullable|string|max:255']);
        $media->update($data);

        return $this->success(array_merge($media->toArray(), ['url' => $media->url()]));
    }

    public function destroy(int $id): JsonResponse
    {
        $media = CmsMedia::findOrFail($id);
        $this->mediaService->delete($media);

        return $this->noContent();
    }
}
