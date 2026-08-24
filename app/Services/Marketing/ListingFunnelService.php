<?php

namespace App\Services\Marketing;

use App\Models\ListingFunnelEvent;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ListingFunnelService
{
    public const LAND = 'land';

    public const ENGAGED = 'engaged';

    public const GALLERY = 'gallery';

    public const CTA_CLICK = 'cta_click';

    public const FORM_OPEN = 'form_open';

    public const FORM_START = 'form_start';

    public const FORM_ERROR = 'form_error';

    public const FORM_CLOSE = 'form_close';

    public const CONVERT = 'convert';

    public const CLIENT_EVENTS = [
        self::ENGAGED,
        self::GALLERY,
        self::CTA_CLICK,
        self::FORM_OPEN,
        self::FORM_START,
        self::FORM_ERROR,
        self::FORM_CLOSE,
    ];

    public function __construct(
        private TrafficAttributionService $trafficAttributionService,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(Request $request, ?int $vehicleId, string $eventName, array $meta = []): void
    {
        if ($vehicleId !== null && $vehicleId > 0 && ! Vehicle::query()->whereKey($vehicleId)->exists()) {
            return;
        }

        $touch = $this->trafficAttributionService->lastTouch($request);
        $sessionId = $this->trafficAttributionService->sessionId($request);
        $trafficSource = (string) ($touch['traffic_source'] ?? TrafficAttributionService::SOURCE_OTHER);

        if ($eventName !== self::FORM_ERROR) {
            $already = ListingFunnelEvent::query()
                ->where('session_id', $sessionId)
                ->where('event_name', $eventName)
                ->where(function ($query) use ($vehicleId) {
                    if ($vehicleId) {
                        $query->where('vehicle_id', $vehicleId);
                    } else {
                        $query->whereNull('vehicle_id');
                    }
                })
                ->exists();

            if ($already) {
                return;
            }
        }

        ListingFunnelEvent::create([
            'session_id' => $sessionId,
            'vehicle_id' => $vehicleId,
            'traffic_source' => $trafficSource,
            'event_name' => $eventName,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);
    }
}
