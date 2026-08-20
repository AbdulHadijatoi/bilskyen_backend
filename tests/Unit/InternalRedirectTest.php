<?php

namespace Tests\Unit;

use App\Support\InternalRedirect;
use Illuminate\Http\Request;
use Tests\TestCase;

class InternalRedirectTest extends TestCase
{
    public function test_allows_same_origin_paths(): void
    {
        $this->assertSame('/saelg-din-bil', InternalRedirect::path('/saelg-din-bil'));
        $this->assertSame('/favoritter?tab=1', InternalRedirect::path('/favoritter?tab=1'));
    }

    public function test_rejects_open_redirects(): void
    {
        $this->assertNull(InternalRedirect::path('https://evil.example/phish'));
        $this->assertNull(InternalRedirect::path('//evil.example'));
        $this->assertNull(InternalRedirect::path('javascript:alert(1)'));
        $this->assertNull(InternalRedirect::path('not-a-path'));
        $this->assertNull(InternalRedirect::path(''));
        $this->assertNull(InternalRedirect::path(null));
    }

    public function test_rewrites_same_host_absolute_urls(): void
    {
        config(['app.url' => 'https://example.test']);

        $this->assertSame(
            '/saelg-din-bil',
            InternalRedirect::path('https://example.test/saelg-din-bil')
        );
        $this->assertNull(InternalRedirect::path('https://other.test/saelg-din-bil'));
    }

    public function test_after_login_honors_return_url(): void
    {
        $request = Request::create('/auth/login', 'POST', [
            'return_url' => '/saelg-din-bil',
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $this->assertSame('/saelg-din-bil', InternalRedirect::afterLogin($request));
    }

    public function test_after_login_rejects_external_return_url(): void
    {
        $request = Request::create('/auth/login', 'POST', [
            'return_url' => 'https://evil.example/phish',
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $this->assertSame('/', InternalRedirect::afterLogin($request));
    }

    public function test_intended_from_request_skips_auth_paths(): void
    {
        $gated = Request::create('/favoritter', 'GET');
        $this->assertSame('/favoritter', InternalRedirect::intendedFromRequest($gated));

        $login = Request::create('/auth/login', 'GET');
        $this->assertNull(InternalRedirect::intendedFromRequest($login));
    }
}
