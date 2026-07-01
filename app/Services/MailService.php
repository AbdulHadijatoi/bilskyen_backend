<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\App;
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
        $locale = config('mail.default_locale', 'da');
        $previousLocale = App::getLocale();

        try {
            App::setLocale($locale);
            $mailable->locale($locale);

            $pendingMail = Mail::to($to)->locale($locale);

            if ($forceQueue === true) {
                $pendingMail->queue($mailable);
            } elseif ($forceQueue === false) {
                $pendingMail->send($mailable);
            } else {
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
        } finally {
            App::setLocale($previousLocale);
        }
    }
}
