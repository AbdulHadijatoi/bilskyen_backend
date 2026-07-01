<?php

namespace App\Services\Branding;

use App\Models\Dealer;
use App\Models\DealerDomain;

class DealerDomainService
{
    public function resolveDealerFromHost(string $host): ?Dealer
    {
        $host = DealerDomain::normalizeDomain($host);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($host === $appHost) {
            return null;
        }

        $domain = DealerDomain::where('domain', $host)
            ->whereNotNull('verified_at')
            ->first();

        return $domain?->dealer;
    }

    public function expectedCnameTarget(): string
    {
        return config('marketplace.custom_domain_cname', parse_url(config('app.url'), PHP_URL_HOST) ?? 'app.bilskyen.dk');
    }

    public function addDomain(Dealer $dealer, string $domain, bool $primary = false): DealerDomain
    {
        $domain = DealerDomain::normalizeDomain($domain);

        if ($primary) {
            DealerDomain::where('dealer_id', $dealer->id)->update(['is_primary' => false]);
        }

        return DealerDomain::create([
            'dealer_id' => $dealer->id,
            'domain' => $domain,
            'verification_token' => DealerDomain::generateVerificationToken(),
            'is_primary' => $primary,
        ]);
    }

    public function verifyDns(DealerDomain $record): bool
    {
        $records = @dns_get_record($record->domain, DNS_CNAME);
        if (! is_array($records)) {
            return false;
        }

        $target = strtolower($this->expectedCnameTarget());
        foreach ($records as $row) {
            $cname = strtolower(rtrim($row['target'] ?? '', '.'));
            if ($cname === $target) {
                $record->update(['verified_at' => now()]);

                return true;
            }
        }

        return false;
    }
}
