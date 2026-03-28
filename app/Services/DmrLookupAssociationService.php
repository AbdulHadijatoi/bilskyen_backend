<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Specification;

class DmrLookupAssociationService
{
    /**
     * Parse DMR comma-separated equipment labels into equipment IDs (create uncatalogued rows with null type).
     *
     * @return array<int>
     */
    public function resolveEquipmentIdsFromLookupString(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return [];
        }
        $ids = [];
        foreach (array_map('trim', explode(',', $csv)) as $segment) {
            if ($segment === '') {
                continue;
            }
            $eq = Equipment::firstOrCreateInsensitive(
                ['name' => $segment],
                ['equipment_type_id' => null]
            );
            $ids[] = (int) $eq->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Parse DMR lookup JSON array of { name, count } into sync payload for vehicle_specifications.
     *
     * @return array<int, array{count: int}>
     */
    public function resolveSpecificationSyncFromLookupJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $sync = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = $row['name'] ?? null;
            if (!is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $count = isset($row['count']) ? (int) $row['count'] : 0;
            $spec = Specification::firstOrCreateInsensitive(['name' => $name], []);
            $sync[(int) $spec->id] = ['count' => max(0, $count)];
        }

        return $sync;
    }
}
