<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Blog Controller
 */
class AdminBlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $blogs = Blog::paginate($request->get('limit', 15));

        return $this->paginated($blogs);
    }

    public function show(int $id): JsonResponse
    {
        $blog = Blog::findOrFail($id);
        return $this->success($blog);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blogs',
            'content' => 'sometimes|string',
            'published_at' => 'sometimes|date',
        ]);

        $blog = Blog::create($request->only(['title', 'slug', 'content', 'published_at']));

        return $this->created($blog);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $blog = Blog::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:blogs,slug,' . $id,
            'content' => 'sometimes|string',
            'published_at' => 'sometimes|date',
        ]);

        $blog->update($request->only(['title', 'slug', 'content', 'published_at']));

        return $this->success($blog);
    }

    public function destroy(int $id): JsonResponse
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return $this->noContent();
    }
}

