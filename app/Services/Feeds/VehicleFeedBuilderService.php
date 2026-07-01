<?php

namespace App\Services\Feeds;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class VehicleFeedBuilderService
{
    /**
     * @return Collection<int, Vehicle>
     */
    public function publishedVehiclesForDealer(Dealer $dealer): Collection
    {
        return Vehicle::with(['images', 'brand', 'fuelType', 'gearType'])
            ->where('dealer_id', $dealer->id)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function mapVehicle(Vehicle $vehicle): array
    {
        $images = $vehicle->images->sortBy('sort_order')->map(function ($img) {
            return url(Storage::disk('public')->url($img->image_path));
        })->values()->all();

        return [
            'id' => $vehicle->id,
            'slug' => $vehicle->slug,
            'title' => $vehicle->title,
            'registration' => $vehicle->registration,
            'price' => $vehicle->price,
            'km_driven' => $vehicle->km_driven,
            'description' => $vehicle->description,
            'brand' => $vehicle->brand?->name,
            'fuel_type' => $vehicle->fuelType?->name,
            'gear_type' => $vehicle->gearType?->name,
            'year' => $vehicle->model_year ?? $vehicle->first_registration_year,
            'images' => $images,
            'primary_image' => $images[0] ?? null,
            'video_url' => $vehicle->video_url,
            'url' => url('/vehicles/'.$vehicle->slug),
            'published_at' => $vehicle->published_at?->toIso8601String(),
        ];
    }

    public function toJson(Dealer $dealer): string
    {
        $items = $this->publishedVehiclesForDealer($dealer)
            ->map(fn (Vehicle $v) => $this->mapVehicle($v))
            ->values();

        return json_encode([
            'dealer_id' => $dealer->id,
            'generated_at' => now()->toIso8601String(),
            'vehicles' => $items,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function toXml(Dealer $dealer): string
    {
        $items = $this->publishedVehiclesForDealer($dealer);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><feed/>');
        $xml->addChild('dealer_id', (string) $dealer->id);
        $xml->addChild('generated_at', now()->toIso8601String());
        $vehiclesNode = $xml->addChild('vehicles');

        foreach ($items as $vehicle) {
            $data = $this->mapVehicle($vehicle);
            $node = $vehiclesNode->addChild('vehicle');
            foreach ($data as $key => $value) {
                if ($key === 'images' && is_array($value)) {
                    $imagesNode = $node->addChild('images');
                    foreach ($value as $url) {
                        $imagesNode->addChild('image', htmlspecialchars((string) $url));
                    }
                    continue;
                }
                if (is_scalar($value) || $value === null) {
                    $node->addChild($key, htmlspecialchars((string) ($value ?? '')));
                }
            }
        }

        return $xml->asXML() ?: '';
    }
}
