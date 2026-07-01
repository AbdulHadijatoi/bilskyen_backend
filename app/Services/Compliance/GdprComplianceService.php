<?php

namespace App\Services\Compliance;

use App\Models\GdprDataRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GdprComplianceService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function exportEnabled(): bool
    {
        $enabled = $this->platformSettingService->get('compliance', 'gdpr_export_enabled', true);

        return ! ($enabled === false || $enabled === 'false');
    }

    public function requestExport(?User $user, string $email): GdprDataRequest
    {
        return GdprDataRequest::create([
            'user_id' => $user?->id,
            'email' => strtolower(trim($email)),
            'type' => 'export',
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    public function processExport(GdprDataRequest $request): GdprDataRequest
    {
        $user = $request->user_id ? User::find($request->user_id) : User::where('email', $request->email)->first();

        $payload = [
            'email' => $request->email,
            'exported_at' => now()->toIso8601String(),
            'user' => $user?->only(['id', 'name', 'email', 'phone', 'created_at']),
            'leads' => [],
            'enquiries' => [],
        ];

        if ($user) {
            $payload['leads'] = Lead::where('buyer_user_id', $user->id)
                ->get()
                ->map(fn (Lead $l) => $l->only(['id', 'dealer_id', 'vehicle_id', 'lead_stage_id', 'created_at']))
                ->values()
                ->all();
        }

        $payload['enquiries'] = \App\Models\Enquiry::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user, fn ($q) => $q->where('email', $request->email))
            ->get()
            ->map(fn ($e) => $e->only(['id', 'subject', 'type', 'status', 'created_at']))
            ->values()
            ->all();

        $filename = 'gdpr/export-'.$request->id.'-'.Str::random(8).'.json';
        Storage::disk('local')->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $request->update([
            'status' => 'completed',
            'download_path' => $filename,
            'completed_at' => now(),
        ]);

        return $request->fresh();
    }

    public function retentionDays(): int
    {
        return max(30, (int) $this->platformSettingService->get('compliance', 'data_retention_days', 730));
    }
}
