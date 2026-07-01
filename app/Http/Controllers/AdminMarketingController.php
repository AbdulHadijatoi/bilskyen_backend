<?php

namespace App\Http\Controllers;

use App\Models\GdprDataRequest;
use App\Models\MarketingEmailQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMarketingController extends Controller
{
    public function queue(Request $request): JsonResponse
    {
        $items = MarketingEmailQueue::orderByDesc('id')
            ->limit($request->integer('limit', 50))
            ->get();

        return $this->success($items);
    }

    public function gdprRequests(Request $request): JsonResponse
    {
        $items = GdprDataRequest::orderByDesc('id')
            ->limit($request->integer('limit', 50))
            ->get();

        return $this->success($items);
    }
}
