<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Services\Branding\BrandedInventoryAuditService;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InventoryAuditController extends Controller
{
    public function __construct(
        private BrandedInventoryAuditService $brandedInventoryAuditService,
    ) {}

    public function show(): View
    {
        return view('inventory-audit', [
            'apiUrl' => url('/api/v1/public/listing-health-audit'),
        ]);
    }

    public function brandedShow(string $slug): View
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw new NotFoundHttpException();
        }

        $dealer = Dealer::with('owner')->where('slug', $slug)->first();
        if (! $dealer) {
            throw new NotFoundHttpException();
        }

        $branding = $this->brandedInventoryAuditService->brandingPayload($dealer);

        return view('inventory-audit-branded', [
            'apiUrl' => url('/api/v1/public/listing-health-audit'),
            'branding' => $branding,
        ]);
    }
}
