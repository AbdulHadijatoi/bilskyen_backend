<?php

namespace App\Mail;

use App\Mail\Concerns\UsesMailLocale;
use App\Models\VehicleImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleImportCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesMailLocale;

    public function __construct(
        public VehicleImportBatch $batch,
    ) {
        $this->applyMailLocale();
    }

    public function envelope(): Envelope
    {
        $summary = $this->batch->summary ?? [];
        $created = (int) ($summary['created'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);

        if ($this->batch->status === VehicleImportBatch::STATUS_FAILED) {
            $subject = __('messages.mail.vehicle_import_failed_subject');
        } elseif ($failed > 0) {
            $subject = __('messages.mail.vehicle_import_completed_with_failures_subject', [
                'created' => $created,
                'failed' => $failed,
            ]);
        } else {
            $subject = __('messages.mail.vehicle_import_completed_subject', ['created' => $created]);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vehicle-import-completed',
        );
    }
}
