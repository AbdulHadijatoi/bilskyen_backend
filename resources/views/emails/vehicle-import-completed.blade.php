<x-mail::message>
# {{ __('messages.mail.vehicle_import_completed_heading') }}

@if($batch->status === \App\Models\VehicleImportBatch::STATUS_FAILED)
{{ __('messages.mail.vehicle_import_failed_intro') }}

**{{ $batch->error_message }}**
@else
{{ __('messages.mail.vehicle_import_completed_intro', ['file' => $batch->original_filename]) }}
@endif

@php
    $summary = $batch->summary ?? [];
    $rows = $batch->rows ?? [];
@endphp

- **{{ __('messages.mail.vehicle_import_total') }}:** {{ $summary['total'] ?? 0 }}
- **{{ __('messages.mail.vehicle_import_created') }}:** {{ $summary['created'] ?? 0 }}
- **{{ __('messages.mail.vehicle_import_failed') }}:** {{ $summary['failed'] ?? 0 }}
- **{{ __('messages.mail.vehicle_import_warnings') }}:** {{ $summary['warnings'] ?? 0 }}

@if(!empty($rows))
## {{ __('messages.mail.vehicle_import_row_details') }}

@foreach($rows as $row)
**{{ __('messages.mail.vehicle_import_row') }} {{ $row['row'] ?? '?' }}** — {{ $row['registration'] ?? '—' }} — *{{ $row['status'] ?? '' }}*

@if(!empty($row['errors']))
@foreach($row['errors'] as $error)
- {{ $error['message'] ?? '' }}
@endforeach
@endif

@if(!empty($row['warnings']))
@foreach($row['warnings'] as $warning)
- {{ $warning['message'] ?? '' }}
@endforeach
@endif

@endforeach
@endif

<x-mail::button :url="rtrim((string) config('payments.panel_url', config('app.url')), '/').'/vehicles'">
{{ __('messages.mail.vehicle_import_view_vehicles') }}
</x-mail::button>

{{ __('messages.mail.thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
