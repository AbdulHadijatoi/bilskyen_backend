<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFaqPageController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pageName = $request->get('page_name', 'faq');
        $sections = $this->pageContentService->getAllSections($pageName);

        return $this->success($sections);
    }

    public function update(Request $request, string $sectionKey): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'faq');
        $pageContent = $this->pageContentService->updateSection(
            $pageName,
            $sectionKey,
            $request->get('content')
        );

        return $this->success($pageContent);
    }

    public function updateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*' => 'nullable|string',
            'page_name' => 'sometimes|string|max:100',
        ]);

        $pageName = $request->get('page_name', 'faq');
        $updated = $this->pageContentService->updateBulk($pageName, $request->get('sections', []));

        return $this->success($updated);
    }
}
