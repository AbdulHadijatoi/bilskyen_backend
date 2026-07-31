# Google Search Console setup (Bilskyen)

Use the **Search Console API** (URL Inspection + Sitemaps) to check whether dealer and vehicle URLs are indexed. Do **not** use the Google Indexing API for marketplace pages — that API is limited to JobPosting / BroadcastEvent content.

## 1. Google Cloud

1. Open [Google Cloud Console](https://console.cloud.google.com/) and select (or create) a project.
2. Enable **Google Search Console API**.
3. Create a **service account** (IAM → Service Accounts → Create).
4. Create a JSON key for that service account and download it.
5. Store the file outside git, e.g. `storage/app/gsc-service-account.json` (ensure `storage/app/*.json` keys are gitignored).

## 2. Search Console property access

1. Open [Google Search Console](https://search.google.com/search-console) for `bilskyen.dk`.
2. Settings → Users and permissions → Add user.
3. Add the service account email (`…@….iam.gserviceaccount.com`) with **Full** permission (Owner is fine).

Use the same property identifier in env:

- URL-prefix property: `https://bilskyen.dk/`
- Domain property: `sc-domain:bilskyen.dk`

## 3. Laravel env

```env
GOOGLE_SEARCH_CONSOLE_PROPERTY=https://bilskyen.dk/
GOOGLE_SERVICE_ACCOUNT_JSON=/absolute/path/to/service-account.json
```

`GOOGLE_SERVICE_ACCOUNT_JSON` may also be raw JSON or base64-encoded JSON.

## 4. Commands

Inspect SEO’d dealer pages and published vehicles:

```bash
php artisan seo:gsc-inspect
php artisan seo:gsc-inspect --dealers-only
php artisan seo:gsc-inspect --vehicles-only --limit=50
php artisan seo:gsc-inspect --submit-sitemap
php artisan seo:gsc-inspect --list-sitemaps
```

URL Inspection is quota-limited (on the order of ~2 000 requests/day). The command throttles between calls (`--sleep=1` by default).

## 5. Interpreting results

| coverageState / indexingState | Meaning |
|-------------------------------|---------|
| Indexed / INDEXING_ALLOWED | URL is in Google’s index (good) |
| Discovered - currently not indexed | Known but not crawled/indexed yet — wait or improve internal links/sitemap |
| Crawled - currently not indexed | Crawled but not chosen for index — check quality/duplicates |
| Excluded by ‘noindex’ / blocked | Fix robots/meta |
| URL is unknown to Google | Ensure sitemap submission + links |

After deploying vehicle meta changes, re-inspect a sample of vehicle URLs and all dealer pages that have admin SEO rows.
