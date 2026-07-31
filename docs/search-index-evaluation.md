# Search index evaluation (Phase 3)

## Context

Bilskyen marketplace search (2026-07) uses:

1. **AI natural-language → filters** (`POST /api/v1/ai/search-parse`) for intent on the homepage / navbar / vehicles Enter key.
2. **SQL `LIKE` + faceted filters** in `VehicleService::applyPublicListingFilters` for inventory matching.
3. **Synonym expansion** (`VehicleSearchSynonymService`) for Danish slang before LIKE / AI context.
4. **Suggest autocomplete** (`GET /api/v1/search/suggest`) from brands/models — no search engine.

## When to add Meilisearch / Typesense / Scout

Introduce a dedicated search index **after** Phase 1–2 metrics show value and pain remains in keyword relevance:

| Signal | Threshold suggestion |
|--------|----------------------|
| Zero-result rate on keyword `search` (non-AI) | Sustained &gt; 15–20% |
| Inventory size | Typically &gt; ~5–10k published listings where LIKE latency or relevance degrades |
| Typo complaints | Users miss “Volswagen”, “Toyata”, etc. |
| Autocomplete latency | Suggest p95 consistently &gt; 200ms from SQL |

## Recommendation

- **Prefer Meilisearch or Typesense** over Elasticsearch for this stack (simpler ops, built-in typo tolerance, fast autocomplete).
- Keep **AI parse as the intent layer**; use the index for ranked full-text on `title`, `brand`, `model`, `description`, registration.
- Sync published vehicles via observers / queued jobs; filter facets can stay on SQL or move to index filters later.
- Do **not** start with a vector DB for v1 semantic ranking — structured filters already cover most buyer intent once AI maps NL → facets.

## Out of scope until metrics

- Embedding-based “similar cars”
- Replacing the filter sidebar with chat-only discovery

## Decision log

| Date | Decision |
|------|----------|
| 2026-07-31 | Defer index; ship AI parse + synonym + suggest first. Revisit when KPIs above are met. |
