<?php

namespace App\Mail\Concerns;

trait UsesMailLocale
{
    protected function applyMailLocale(): void
    {
        $locale = config('mail.default_locale', 'da');
        $this->locale($locale);
    }
}
