<?php

namespace App\Services\Dms;

use App\Models\Dealer;
use App\Models\DealerApiKey;
use App\Models\DealerWebhookDelivery;
use App\Models\DealerWebhookEndpoint;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DealerDmsService
{
    public function createApiKey(Dealer $dealer, string $name): array
    {
        $plain = DealerApiKey::generatePlainKey();
        $record = DealerApiKey::create([
            'dealer_id' => $dealer->id,
            'name' => $name,
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => DealerApiKey::hashKey($plain),
            'permissions' => ['vehicles.upsert'],
        ]);

        return ['key' => $record, 'plain_key' => $plain];
    }

    public function resolveDealerFromApiKey(string $plainKey): ?Dealer
    {
        $hash = DealerApiKey::hashKey($plainKey);
        $record = DealerApiKey::where('key_hash', $hash)->first();
        if (! $record) {
            return null;
        }

        $record->update(['last_used_at' => now()]);

        return $record->dealer;
    }

    public function createWebhook(Dealer $dealer, string $url, array $events): DealerWebhookEndpoint
    {
        return DealerWebhookEndpoint::create([
            'dealer_id' => $dealer->id,
            'url' => $url,
            'secret' => DealerWebhookEndpoint::generateSecret(),
            'events' => $events,
            'enabled' => true,
        ]);
    }

    public function dispatchVehicleEvent(Vehicle $vehicle, string $event): void
    {
        if (! $vehicle->dealer_id) {
            return;
        }

        $endpoints = DealerWebhookEndpoint::where('dealer_id', $vehicle->dealer_id)
            ->where('enabled', true)
            ->get()
            ->filter(fn (DealerWebhookEndpoint $ep) => in_array($event, $ep->events ?? [], true));

        $payload = [
            'event' => $event,
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $vehicle->dealer_id,
            'slug' => $vehicle->slug,
            'title' => $vehicle->title,
            'price' => $vehicle->price,
            'list_status_id' => $vehicle->list_status_id,
            'published_at' => $vehicle->published_at?->toIso8601String(),
            'updated_at' => $vehicle->updated_at?->toIso8601String(),
        ];

        foreach ($endpoints as $endpoint) {
            $this->deliver($endpoint, $event, $payload);
        }
    }

    private function deliver(DealerWebhookEndpoint $endpoint, string $event, array $payload): void
    {
        $status = 'failed';
        $responseCode = null;
        $responseBody = null;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Bilskyen-Event' => $event,
                    'X-Bilskyen-Signature' => hash_hmac('sha256', json_encode($payload), $endpoint->secret ?? ''),
                ])
                ->post($endpoint->url, $payload);

            $responseCode = $response->status();
            $responseBody = Str::limit($response->body(), 2000);
            $status = $response->successful() ? 'success' : 'failed';
        } catch (\Throwable $e) {
            $responseBody = $e->getMessage();
            Log::warning('DMS webhook delivery failed', ['endpoint_id' => $endpoint->id, 'error' => $e->getMessage()]);
        }

        DealerWebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $payload,
            'status' => $status,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
        ]);
    }
}
