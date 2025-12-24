<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Constants\PageStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Admin Page Controller
 */
class AdminPageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pages = Page::paginate($request->get('limit', 15));

        return $this->paginated($pages);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);
        return $this->success($page);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages',
            'content' => 'sometimes|string',
            'page_status_id' => ['required', Rule::in(PageStatus::values())],
        ]);

        $page = Page::create($request->only(['title', 'slug', 'content', 'page_status_id']));

        return $this->created($page);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:pages,slug,' . $id,
            'content' => 'sometimes|string',
            'page_status_id' => ['sometimes', Rule::in(PageStatus::values())],
        ]);

        $page->update($request->only(['title', 'slug', 'content', 'page_status_id']));

        return $this->success($page);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return $this->noContent();
    }

    public function publish(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);
        $page->page_status_id = PageStatus::PUBLISHED;
        $page->save();

        return $this->success($page);
    }
}

