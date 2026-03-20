# Flutter Lookup Dropdowns (Brands / Models / Types / Variants)

## Overview
The public `/api/v1/constants` endpoint is now **lightweight** and no longer includes:
- `brands`
- `models`
- `types`
- `variants`

Flutter must fetch these datasets via the new dedicated lookup/search endpoints and implement **typeahead/search** dropdown behavior that does **not** load full tables at once.

## Base URLs
- API base path: `/api/v1`

## Unified Response Shape
All lookup endpoints return the same envelope (from the backend `ApiResponse` helper):

```json
{
  "success": true,
  "failed": false,
  "message": "...",
  "data": {
    "items": [ ... ],
    "limit": 25
  },
  "errors": []
}
```

Flutter should parse:
- `success` / `failed`
- `data.items` for results
- `message` for error UI/logging

## Endpoints
### Brands (multi-select / checkbox list)
`GET /api/v1/brands`

Query params:
- `search` (string, optional)
- `limit` (number, optional; backend caps results)

Returned items:
- `{ "id": number, "name": string }`

### Models (multi-select / checkbox list, constrained by brands)
`GET /api/v1/models`

Query params:
- `search` (string, optional)
- `limit` (number, optional; backend caps results)
- `brand_ids` (comma-separated IDs, optional)

Returned items:
- `{ "id": number, "name": string, "brand_id": number }`

Behavior rule:
- When `brand_ids` is empty (no brands selected), you can either:
  - disable the Models dropdown UX, or
  - allow model search without constraints (web behavior disables/clears models when no brands are selected).

### Types (single-select)
`GET /api/v1/types`

Query params:
- `search` (string, optional)
- `limit` (number, optional; backend caps results)

Returned items:
- `{ "id": number, "name": string }`

### Variants (optional for future; not required for vehicles typeahead right now)
`GET /api/v1/variants`

Query params:
- `search` (string, optional)
- `limit` (number, optional; backend caps results)
- `model_ids` (comma-separated IDs, optional)

Returned items:
- `{ "id": number, "name": string, "model_id": number }`

## Typeahead / Dropdown UX Rules (Important)
### Shared rules
1. **Debounce** the search input (e.g. 250–400ms) to avoid spamming the API.
2. **Preserve selections**:
   - If a user selects items and then searches again, keep selected items visible (even if they are not in the latest result list).
3. Avoid full-table loading:
   - Only render results returned from the API.
4. Handle race conditions:
   - Use a request token / incrementing counter so outdated responses do not overwrite newer results.

### Brands dropdown (multi-select)
1. Start with an empty/initial list of suggestions.
2. On search input change:
   - call `/api/v1/brands?search=<term>&limit=25`
3. Update the checkbox list with returned brands.
4. Ensure already-selected brands remain checked and remain in the list.

### Models dropdown (multi-select)
1. Models should be constrained by selected brands:
   - send `brand_ids=<comma-separated-selected-brand-ids>`
2. On search input change:
   - call `/api/v1/models?search=<term>&brand_ids=1,2&limit=25`
3. Update the checkbox list with returned models.
4. Ensure already-selected models remain checked and remain in the list.

### Types dropdown (single-select)
1. Show an “All” option (value `""`) plus dynamic results.
2. On search input change:
   - call `/api/v1/types?search=<term>&limit=25`
3. Update the `<option>`/list items with returned types.
4. Ensure the currently selected type stays present in the options list.

## Example Requests (cURL)
```bash
curl 'https://<host>/api/v1/brands?search=to&limit=25'

curl 'https://<host>/api/v1/models?search=cor&brand_ids=1,2&limit=25'

curl 'https://<host>/api/v1/types?search=hatch&limit=25'
```

## Suggested Flutter Implementation Approach (Pseudo-code)
### API Client (shape)
Define a method that returns `List<T>` from `data.items` and throws/returns error state on `success=false`.

### State (high-level)
Maintain:
- `Set<int> selectedBrandIds`
- `Map<int, String> selectedBrandIdToName` (for preserving selections)
- `Set<int> selectedModelIds`
- `Map<int, String> selectedModelIdToName`
- `int? selectedTypeId`
- `String? selectedTypeName` (for preserving)

### Dropdown refresh logic
On each search change:
1. increment request token
2. call endpoint
3. if token is still current:
   - replace suggestion list
   - re-insert selected items that may be missing from results

## Error Handling
If the API call fails:
- do not clear existing selected items
- keep the current suggestion list (or set it to empty)
- show a non-blocking toast/snackbar (optional)

## Notes
- Backend caps `limit` server-side; you can default to `25`.
- Variants endpoint exists for completeness but is not required for the vehicles filters update described here.

