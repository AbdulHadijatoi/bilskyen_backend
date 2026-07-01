<?php

namespace App\Services;

use App\Models\OwnershipTaxRule;
use App\Models\Vehicle;

class OwnershipTaxService
{
    /**
     * Returns a matching rule for a vehicle, if any.
     */
    public function findRuleForVehicle(Vehicle $vehicle): ?OwnershipTaxRule
    {
        $registrationYear = $this->getRegistrationYear($vehicle);
        $kmPerLiter = $this->getKmPerLiter($vehicle);
        $driveEnergyId = $this->getPrimaryDriveEnergyId($vehicle);

        if ($registrationYear === null || $kmPerLiter === null || $driveEnergyId === null) {
            return null;
        }

        return OwnershipTaxRule::query()
            ->where('dmr_drive_energy_id', $driveEnergyId)
            ->where('registration_year_from', '<=', $registrationYear)
            ->where('registration_year_to', '>=', $registrationYear)
            ->where('km_per_liter_from', '<=', $kmPerLiter)
            ->where('km_per_liter_to', '>=', $kmPerLiter)
            // Prefer the most specific (tightest ranges) when overlaps exist
            ->orderByRaw('(registration_year_to - registration_year_from) asc')
            ->orderByRaw('(km_per_liter_to - km_per_liter_from) asc')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Returns the ownership tax amount for a vehicle (tax_amount), if matched.
     */
    public function calculateForVehicle(Vehicle $vehicle): ?int
    {
        $rule = $this->findRuleForVehicle($vehicle);
        return $rule?->tax_amount;
    }

    public function updateCalculatedOwnershipTax(Vehicle $vehicle): Vehicle
    {
        $tax = $this->calculateForVehicle($vehicle);
        $vehicle->calculated_ownership_tax = $tax;
        $vehicle->save();

        return $vehicle;
    }

    /**
     * Recompute stored tax for all vehicles (used when rule tables change).
     */
    public function recalculateAllVehicles(int $chunkSize = 500): void
    {
        Vehicle::query()
            ->with(['dmrFactVehicle.drivmiddelLines'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($vehicles) {
                foreach ($vehicles as $vehicle) {
                    $this->updateCalculatedOwnershipTax($vehicle);
                }
            });
    }

    private function getRegistrationYear(Vehicle $vehicle): ?int
    {
        $firstReg = $vehicle->first_registration_date;
        if ($firstReg) {
            try {
                return (int) $firstReg->format('Y');
            } catch (\Throwable) {
                // fall through to first_registration_year
            }
        }

        $y = $vehicle->first_registration_year;
        if ($y !== null && $y !== '') {
            $n = (int) $y;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    private function getPrimaryDriveEnergyId(Vehicle $vehicle): ?int
    {
        $fv = $vehicle->relationLoaded('dmrFactVehicle') ? $vehicle->dmrFactVehicle : $vehicle->dmrFactVehicle()->first();
        if (! $fv) {
            return null;
        }

        $lines = $fv->relationLoaded('drivmiddelLines')
            ? $fv->drivmiddelLines
            : $fv->drivmiddelLines()->orderBy('line_order')->get();

        if (! $lines || $lines->isEmpty()) {
            return null;
        }

        $sorted = $lines->sortBy('line_order')->values();
        $primary = $sorted->first(fn ($line) => (bool) $line->drivmiddel_primaer) ?? $sorted->first();

        $id = $primary?->drive_energy_id;
        return $id !== null ? (int) $id : null;
    }

    private function getKmPerLiter(Vehicle $vehicle): ?float
    {
        $fv = $vehicle->relationLoaded('dmrFactVehicle') ? $vehicle->dmrFactVehicle : $vehicle->dmrFactVehicle()->first();
        if (! $fv) {
            return null;
        }

        $lines = $fv->relationLoaded('drivmiddelLines')
            ? $fv->drivmiddelLines
            : $fv->drivmiddelLines()->orderBy('line_order')->get();

        if (! $lines || $lines->isEmpty()) {
            return null;
        }

        $sorted = $lines->sortBy('line_order')->values();
        $primary = $sorted->first(fn ($line) => (bool) $line->drivmiddel_primaer) ?? $sorted->first();

        $kmPerLiter = $primary?->motor_km_per_liter;
        if ($kmPerLiter === null) {
            return null;
        }

        return (float) $kmPerLiter;
    }
}

