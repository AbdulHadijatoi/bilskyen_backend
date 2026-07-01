<?php

namespace App\Services\Syndication;

use App\Constants\SyndicationProviderKey;
use App\Contracts\SyndicationProviderInterface;
use App\Models\Dealer;
use App\Models\DealerSyndicationSetting;
use App\Models\SyndicationLog;
use App\Models\Vehicle;
use App\Services\PlatformSettingService;
use App\Services\Syndication\Providers\FacebookCatalogSyndicationProvider;
use App\Services\Syndication\Providers\GenericJsonSyndicationProvider;
use App\Services\Syndication\Providers\GenericXmlSyndicationProvider;
use Illuminate\Support\Collection;

class SyndicationService
{
    /** @var array<string, SyndicationProviderInterface> */
    private array $providers;

    public function __construct(
        private PlatformSettingService $platformSettingService,
        GenericJsonSyndicationProvider $json,
        GenericXmlSyndicationProvider $xml,
        FacebookCatalogSyndicationProvider $facebook,
    ) {
        $this->providers = [
            $json->key() => $json,
            $xml->key() => $xml,
            $facebook->key() => $facebook,
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function availableProviders(): array
    {
        return collect($this->providers)->map(fn ($p) => [
            'key' => $p->key(),
            'label' => $p->label(),
        ])->values()->all();
    }

    public function provider(string $key): ?SyndicationProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    public function syncVehicle(Vehicle $vehicle, string $action = 'upsert'): void
    {
        if (! $vehicle->dealer_id) {
            return;
        }

        if (! filter_var($this->platformSettingService->get('syndication', 'auto_sync_on_publish', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $dealer = $vehicle->dealer;
        if (! $dealer) {
            return;
        }

        $settings = DealerSyndicationSetting::where('dealer_id', $dealer->id)
            ->where('enabled', true)
            ->get();

        foreach ($settings as $setting) {
            $provider = $this->provider($setting->provider_key);
            if (! $provider) {
                continue;
            }

            try {
                $result = $provider->syncVehicle($dealer, $vehicle, $action);
                $this->log($dealer->id, $vehicle->id, $setting->provider_key, $action, 'success', $result['external_listing_id'] ?? null, $result['message'] ?? null);
                $setting->update(['last_sync_at' => now()]);
            } catch (\Throwable $e) {
                $this->log($dealer->id, $vehicle->id, $setting->provider_key, $action, 'failed', null, $e->getMessage());
            }
        }
    }

    public function syncDealer(Dealer $dealer): int
    {
        $count = 0;
        $vehicles = app(\App\Services\Feeds\VehicleFeedBuilderService::class)->publishedVehiclesForDealer($dealer);

        foreach (DealerSyndicationSetting::where('dealer_id', $dealer->id)->where('enabled', true)->get() as $setting) {
            $provider = $this->provider($setting->provider_key);
            if (! $provider) {
                continue;
            }
            foreach ($vehicles as $vehicle) {
                try {
                    $result = $provider->syncVehicle($dealer, $vehicle, 'sync');
                    $this->log($dealer->id, $vehicle->id, $setting->provider_key, 'sync', 'success', $result['external_listing_id'] ?? null, null);
                    $count++;
                } catch (\Throwable $e) {
                    $this->log($dealer->id, $vehicle->id, $setting->provider_key, 'sync', 'failed', null, $e->getMessage());
                }
            }
            $setting->update(['last_sync_at' => now()]);
        }

        return $count;
    }

    /**
     * @return Collection<int, SyndicationLog>
     */
    public function recentLogs(?int $dealerId = null, int $limit = 50): Collection
    {
        $query = SyndicationLog::orderByDesc('id')->limit($limit);
        if ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        return $query->get();
    }

    private function log(
        int $dealerId,
        ?int $vehicleId,
        string $providerKey,
        string $action,
        string $status,
        ?string $externalId,
        ?string $message
    ): void {
        SyndicationLog::create([
            'dealer_id' => $dealerId,
            'vehicle_id' => $vehicleId,
            'provider_key' => $providerKey,
            'action' => $action,
            'status' => $status,
            'external_listing_id' => $externalId,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
