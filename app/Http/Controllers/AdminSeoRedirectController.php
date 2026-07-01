<?php

namespace App\Http\Controllers;

use App\Models\SeoRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSeoRedirectController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(
            SeoRedirect::orderByDesc('updated_at')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateRedirect($request);
        $redirect = SeoRedirect::create($data);

        return $this->created($redirect);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $redirect = SeoRedirect::findOrFail($id);
        $redirect->update($this->validateRedirect($request, $redirect->id));

        return $this->success($redirect);
    }

    public function destroy(int $id): JsonResponse
    {
        SeoRedirect::findOrFail($id)->delete();

        return $this->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRedirect(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:500', Rule::unique('seo_redirects', 'from_path')->ignore($id)],
            'to_path' => 'required|string|max:500',
            'redirect_type' => 'required|integer|in:301,302',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['from_path'] = SeoRedirect::normalizePath($data['from_path']);

        return $data;
    }
}
