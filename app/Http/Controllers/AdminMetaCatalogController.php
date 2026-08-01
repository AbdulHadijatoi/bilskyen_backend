<?php

namespace App\Http\Controllers;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\PlatformSettingService;
use App\Services\Syndication\MetaCatalogFeedUrlService;
use App\Services\Syndication\MetaVehicleCatalogMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMetaCatalogController extends Controller
{
    public function __construct(
        private MetaVehicleCatalogMapper $catalogMapper,
        private PlatformSettingService $platformSettingService,
        private MetaCatalogFeedUrlService $feedUrlService,
    ) {}

    public function preview(Request $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->load($this->catalogMapper->eagerLoads());
        $preview = $this->catalogMapper->preview($vehicle);

        return $this->success([
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'slug' => $vehicle->slug,
                'detail_url' => route('vehicle.detail', $vehicle),
                'list_status_id' => $vehicle->list_status_id,
                'is_published' => (int) $vehicle->list_status_id === VehicleListStatus::PUBLISHED,
            ],
            'row' => $preview['row'],
            'readiness' => $preview['readiness'],
            'ready' => $preview['ready'],
            'feed_url' => $this->feedUrlService->platformFeedUrl(),
            'pixel_enabled' => filter_var(
                $this->platformSettingService->get('marketing', 'meta_pixel_enabled', false),
                FILTER_VALIDATE_BOOLEAN
            ),
            'pixel_id' => (string) $this->platformSettingService->get('marketing', 'meta_pixel_id', ''),
        ]);
    }

    public function feedUrl(Request $request): JsonResponse
    {
        return $this->success([
            'feed_url' => $this->feedUrlService->platformFeedUrl(),
            'pixel_enabled' => filter_var(
                $this->platformSettingService->get('marketing', 'meta_pixel_enabled', false),
                FILTER_VALIDATE_BOOLEAN
            ),
            'pixel_id' => (string) $this->platformSettingService->get('marketing', 'meta_pixel_id', ''),
        ]);
    }
}
