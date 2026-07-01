<?php

namespace App\Http\Controllers;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DmsInboundController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService,
    ) {}

    public function upsertVehicle(Request $request): JsonResponse
    {
        /** @var \App\Models\Dealer $dealer */
        $dealer = $request->attributes->get('dms_dealer');

        $data = $request->validate([
            'external_id' => 'nullable|string|max:128',
            'registration' => 'nullable|string|max:32',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'km_driven' => 'nullable|integer|min:0',
            'publish' => 'sometimes|boolean',
        ]);

        $vehicle = null;
        if (! empty($data['registration'])) {
            $vehicle = Vehicle::where('dealer_id', $dealer->id)
                ->where('registration', $data['registration'])
                ->first();
        }

        $payload = [
            'dealer_id' => $dealer->id,
            'title' => $data['title'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'km_driven' => $data['km_driven'] ?? null,
            'registration' => $data['registration'] ?? null,
            'list_status_id' => ($data['publish'] ?? false)
                ? VehicleListStatus::PUBLISHED
                : VehicleListStatus::DRAFT,
        ];

        if ($vehicle) {
            $vehicle->update($payload);
        } else {
            $vehicle = Vehicle::create($payload);
        }

        return $this->success(['vehicle_id' => $vehicle->id, 'created' => $vehicle->wasRecentlyCreated]);
    }
}
