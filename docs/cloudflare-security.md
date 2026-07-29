# Cloudflare edge protection for Bilskyen

Use this checklist when DNS for `bilskyen.dk` and `panel.bilskyen.dk` can sit behind Cloudflare. App-level rate limits, Turnstile, honeypot, and security headers still apply without Cloudflare.

## 1. Proxy the zones

1. Add both hostnames to a Cloudflare zone (orange-cloud / proxied).
2. Set SSL/TLS mode to **Full (strict)** with a valid origin certificate.
3. Enable **Always Use HTTPS**.

## 2. Bot Fight / Super Bot Fight

1. Security → Bots → enable **Bot Fight Mode** (or Super Bot Fight on paid plans).
2. Prefer “Definitely automated” → Block, “Likely automated” → Managed Challenge.

## 3. WAF custom rules (examples)

Create rules that **Managed Challenge** or **Block** when rate/path matches:

| Rule name | Expression (approximate) | Action |
|-----------|--------------------------|--------|
| Scrape listings API | `(http.request.uri.path contains "/api/v1/vehicles" or http.request.uri.path eq "/api/v1/search-vehicles" or http.request.uri.path eq "/api/v1/constants")` | Rate limit + challenge |
| Auth brute force | `http.request.uri.path contains "/api/v1/auth/" and http.request.method eq "POST"` | Rate limit |
| Empty user-agent | `not http.user_agent` | Block |

Also use Cloudflare **Rate limiting** rules:

- `/api/v1/vehicles*` — e.g. 60 req/min per IP
- `/api/v1/search-vehicles` — e.g. 60 req/min per IP
- `/api/v1/auth/login`, `panel-login`, `staff-login` — e.g. 10 req/min per IP

## 4. Turnstile keys

1. Cloudflare Dashboard → Turnstile → create a widget (Managed or Invisible).
2. Set on the Laravel API host:

```env
SECURITY_HARDENING=true
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
```

3. Set on the Vue panel:

```env
VITE_TURNSTILE_SITE_KEY=...   # same site key
```

## 5. Real client IPs

Laravel trusts proxies (`bootstrap/app.php` → `trustProxies(at: '*')`). Confirm Cloudflare is sending `CF-Connecting-IP` / `X-Forwarded-For` so named rate limiters key on the visitor IP, not the CDN edge.

## 6. Optional feed allowlist

Partner vehicle feeds (`/api/v1/feeds/{token}/...`) accept an IP allowlist:

```env
SECURITY_FEED_IP_ALLOWLIST=1.2.3.4,5.6.7.8
```

Empty allowlist = any IP with a valid feed token (still rate-limited).

## 7. CSP enforcement

Headers ship in **Report-Only** by default. After reviewing browser consoles in production:

```env
SECURITY_CSP_REPORT_ONLY=false
```

## 8. Verify

1. Hit `/api/v1/vehicles?limit=999` — server should clamp page size (max 48 by default).
2. Rapid-fire listing requests — expect HTTP 429.
3. Submit contact/login without Turnstile token in production — expect 422.
4. Confirm `robots.txt` still disallows `/api/`.
