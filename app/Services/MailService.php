<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailService
{
    /**
     * Send a mailable through the default configured mailer.
     *
     * @param  string|array<int, string>  $to
     * @param  array<string, mixed>  $context
     */
    public function sendMailable(string|array $to, Mailable $mailable, array $context = [], ?bool $forceQueue = null): bool
    {
        try {
            $pendingMail = Mail::to($to)->locale(config('mail.default_locale', 'da'));

            if ($forceQueue === true) {
                $pendingMail->queue($mailable);
            } elseif ($forceQueue === false) {
                $pendingMail->send($mailable);
            } else {
                // Keep Laravel default behavior (supports ShouldQueue mailables).
                $pendingMail->send($mailable);
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('Failed to send email', array_merge($context, [
                'to' => $to,
                'mailable' => $mailable::class,
                'error' => $exception->getMessage(),
            ]));

            return false;
        }
    }
}
