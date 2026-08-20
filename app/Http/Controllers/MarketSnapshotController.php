<?php

namespace App\Http\Controllers;

use App\Services\MarketSnapshotService;
use App\Services\SeoService;
use Illuminate\View\View;

class MarketSnapshotController extends Controller
{
    public function __construct(
        private MarketSnapshotService $snapshotService,
        private SeoService $seoService,
    ) {}

    public function show(): View
    {
        $snapshot = $this->snapshotService->snapshot();
        $seo = $this->seoService->resolveForMarketSnapshot($snapshot);

        return view('market-snapshot', compact('snapshot', 'seo'));
    }
}
