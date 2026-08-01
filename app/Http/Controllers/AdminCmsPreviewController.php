<?php

namespace App\Http\Controllers;

use App\Services\Cms\CmsPreviewRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminCmsPreviewController extends Controller
{
    public function __construct(
        private CmsPreviewRenderer $renderer
    ) {}

    public function landing(Request $request): Response
    {
        $html = $this->renderer->renderLanding($request->all());

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function blog(Request $request): Response
    {
        $html = $this->renderer->renderBlog($request->all());

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
