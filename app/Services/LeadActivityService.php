<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

class LeadActivityService
{
    public function log(
        Lead $lead,
        string $type,
        string $title,
        ?User $user = null,
        ?array $meta = null
    ): LeadActivity {
        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $title,
            'meta' => $meta,
            'created_at' => now(),
        ]);

        $lead->update(['last_activity_at' => now()]);

        return $activity;
    }
}
