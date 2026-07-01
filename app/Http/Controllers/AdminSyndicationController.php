<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Services\Feeds\SftpFeedUploadService;
use App\Services\Syndication\SyndicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSyndicationController extends Controller
{
    public function __construct(
        private SyndicationService $syndicationService,
        private SftpFeedUploadService $sftpFeedUploadService,
    ) {}

    public function providers(): JsonResponse
    {
        return $this->success($this->syndicationService->availableProviders());
    }

    public function logs(Request $request): JsonResponse
    {
        $dealerId = $request->integer('dealer_id') ?: null;

        return $this->success($this->syndicationService->recentLogs($dealerId, $request->integer('limit', 50)));
    }

    public function syncDealer(int $dealerId): JsonResponse
    {
        $dealer = Dealer::findOrFail($dealerId);
        $count = $this->syndicationService->syncDealer($dealer);

        return $this->success(['synced' => $count]);
    }

    public function testSftp(): JsonResponse
    {
        return $this->success($this->sftpFeedUploadService->testConnection());
    }

    public function uploadSftp(): JsonResponse
    {
        return $this->success($this->sftpFeedUploadService->uploadPlatformFeed());
    }
}
