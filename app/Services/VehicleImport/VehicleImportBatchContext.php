<?php

namespace App\Services\VehicleImport;

/**
 * Tracks in-file state during a bulk import (quota simulation, duplicate plates, DMR cache).
 */
class VehicleImportBatchContext
{
    /** @var array<string, true> */
    public array $seenRegistrations = [];

    /** @var array<string, array<string, mixed>> */
    public array $dmrCache = [];

    public function __construct(
        public int $publishedCount,
    ) {}

    public function markRegistrationSeen(string $normalizedRegistration): void
    {
        if ($normalizedRegistration !== '') {
            $this->seenRegistrations[$normalizedRegistration] = true;
        }
    }

    public function hasSeenRegistration(string $normalizedRegistration): bool
    {
        return $normalizedRegistration !== ''
            && isset($this->seenRegistrations[$normalizedRegistration]);
    }

    public function incrementPublishedCount(): void
    {
        $this->publishedCount++;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCachedDmr(string $key): ?array
    {
        return $this->dmrCache[$key] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function cacheDmr(string $key, array $payload): void
    {
        $this->dmrCache[$key] = $payload;
    }
}
