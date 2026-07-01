<?php

namespace App\Http\Controllers;

use App\Models\DealerInvoice;
use App\Services\DealerInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDealerInvoiceController extends Controller
{
    public function __construct(
        private DealerInvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DealerInvoice::query()
            ->with([
                'dealer:id,user_id,slug,cvr,city',
                'dealer.owner:id,name,email',
                'lines',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('dealer_id')) {
            $query->where('dealer_id', $request->integer('dealer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->paginated($query->paginate($request->integer('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $invoice = DealerInvoice::with(['dealer.owner', 'lines.vehicle', 'approvedBy'])->findOrFail($id);

        return $this->success($invoice);
    }

    public function markSent(int $id): JsonResponse
    {
        $invoice = DealerInvoice::findOrFail($id);

        return $this->success($this->invoiceService->markSent($invoice));
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $invoice = DealerInvoice::findOrFail($id);

        return $this->success(
            $this->invoiceService->markPaid($invoice, $request->user()->id)
        );
    }
}
