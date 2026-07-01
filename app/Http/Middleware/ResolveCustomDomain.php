<?php

namespace App\Http\Middleware;

use App\Services\Branding\DealerDomainService;
use Closure;
use Illuminate\Http\Request;

class ResolveCustomDomain
{
    public function __construct(
        private DealerDomainService $dealerDomainService,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $dealer = $this->dealerDomainService->resolveDealerFromHost($request->getHost());
        if ($dealer) {
            $request->attributes->set('custom_domain_dealer', $dealer);
        }

        return $next($request);
    }
}
