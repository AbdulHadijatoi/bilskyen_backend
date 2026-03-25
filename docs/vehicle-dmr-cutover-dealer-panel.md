# Vehicle DMR-only Cutover (Dealer panel contract)

This document describes the backend changes required for the **dealer VueJS panel** after the `vehicles` table was cut over to a **DMR-linked** model.

## 1. What changed (high level)

1. `vehicles` is now the primary persisted listing row and is linked to DMR fact data via `dmr_fact_vehicle_id`.
2. The dealer panel must treat `dmr_fact_vehicle_id` as the canonical “vehicle identity” for create flows.
3. Legacy selection/filtering fields (ex: `brand_id`, `model_id`, `fuel_type_id`) are no longer stored on the `vehicles` table row. They are available via DMR joins/accessors and are used only for filtering/search.
4. Legacy `vehicle_details` is not used for the new listing shape in APIs; for Blade parity, `$vehicle->details` is now a read-only presenter backed by DMR.

## 2. Canonical persisted `vehicles` columns (create/update)

When the dealer panel creates or updates a listing, the backend will persist only the following “slim” column set on the `vehicles` row:

```text
dmr_fact_vehicle_id
user_id
dealer_id
title
slug
registration
price
vehicle_list_status_id
published_at
description
address
postcode
gear_type_id
km_driven
battery_capacity
range_km
charging_type
condition_id
servicebog
```

Notes:

1. `dealer_id` and `user_id` are set by the server for dealer endpoints (Vue should not send them).
2. `slug` is generated automatically when creating/updating if not provided.
3. For sell-your-car web form inputs, `seller_address` -> `vehicles.address` and `seller_postcode` -> `vehicles.postcode`.

## 3. Dealer panel API endpoints (what to call)

Base URL: `/api/v1/dealer/...`

All dealer routes require `auth:api` and relevant dealer permissions.

### 3.1 List vehicles

`GET /api/v1/dealer/vehicles`

Query params:

1. `search` (optional)
2. `vehicle_list_status_id` (optional)
3. `sort` (optional; default handled by backend)
4. `page`, `limit` (optional)

Response shape:

The response is a paginated wrapper:

```json
{
  "success": true,
  "data": {
    "docs": [ /* vehicle objects */ ],
    "limit": 15,
    "page": 1,
    "hasPrevPage": false,
    "hasNextPage": true,
    "prevPage": null,
    "nextPage": 2,
    "totalDocs": 123,
    "totalPages": 9
  }
}
```

Each vehicle object includes:

1. Columns from `vehicles` (the slim set above)
2. Computed “DMR accessors” via the `Vehicle` model (examples: `brand_name`, `model_name`, `fuel_type_name`, `model_year_name`, `engine_power_hp`, `first_registration_date`, `vehicle_list_status_name`)
3. Relations loaded for list views: `images`, `equipment`, and `dmrFactVehicle.variant.model.brand`

### 3.2 Show a vehicle (dealer)

`GET /api/v1/dealer/vehicles/show/{id}`

Response:

`success: true` with `data` set to the vehicle model + additional eager-loaded relations (images, equipment + types, price history, condition/gear type, DMR drivmiddel energy line, etc.).

### 3.3 Create vehicle (dealer)

`POST /api/v1/dealer/vehicles`

Request:

1. Must be `multipart/form-data` if uploading `images`.
2. Send `images` as files (if present) under the `images[]` field.
3. Send equipment IDs under `equipment_ids` as an array.

Required fields (for DMR-only listing creation):

1. `dmr_fact_vehicle_id` (required; service rejects creation without it)
2. `price` (required)
3. `vehicle_list_status_id` (required)
4. `registration` (optional per controller validation, but may be used in UI/search)

Optional fields from the slim set:

1. `title` (recommended; used for slug and UI)
2. `published_at` (optional; if you publish via status update, backend can set it)
3. `description`
4. `address`, `postcode`
5. `gear_type_id`, `condition_id`
6. `km_driven`, `battery_capacity`, `range_km`, `charging_type`
7. `servicebog`

Important:

- Dealer `create` endpoint validates only `registration`/`vin` fields at controller-level, but `VehicleService::createVehicle()` enforces `dmr_fact_vehicle_id`.

### 3.4 Create draft

`POST /api/v1/dealer/vehicles/draft`

Same payload rules as create.

Backend behavior:

- It sets `vehicle_list_status_id = 1` (Draft) server-side.

### 3.5 Update vehicle

`POST /api/v1/dealer/vehicles/update/{id}`

Same fields as create.

Equipment:

- If you send `equipment_ids` as an array, the backend syncs the pivot (`vehicle_equipment`) to match exactly.

Images:

- If you send `images` as file uploads, the backend replaces existing vehicle images.

### 3.6 Publish/unpublish/change status

`POST /api/v1/dealer/vehicles/update-status/{id}`

Payload:

Option A:

- `status`: one of `published, unpublished, archived, draft, sold`

Option B:

- `vehicle_list_status_id`: a valid `vehicle_list_statuses.id`

Backend behavior:

1. If you set status to `published` and `published_at` is null, the backend sets `published_at = now()`.
2. Publish limit checks (`max_listings`) are enforced when changing to published.

### 3.7 Upload images (after create)

`POST /api/v1/dealer/vehicles/{id}/images`

Request:

1. multipart/form-data
2. `images` is required (array) and each image must be valid image type and size per controller validation

Backend behavior:

- It appends images and sets `sort_order` after the existing max `sort_order`.

### 3.8 Fetch vehicle data from Nummerplade (dealer helper)

`POST /api/v1/dealer/vehicles/fetch-from-nummerplade`

Request:

1. Provide either `registration` OR `vin` (validation uses `required_without`)

Response:

- Transformed vehicle attributes intended to be pasted into the create/update payload.
- Ensure you send (or map) `dmr_fact_vehicle_id` into the final dealer create/update request.

## 4. Sell-your-car web form mapping (for completeness)

The public “Sell your car” web form persists a `Vehicle` row directly (not `VehicleListing`).

During `SellYourCarController::store`:

1. `seller_address` -> `vehicles.address`
2. `seller_postcode` -> `vehicles.postcode`
3. `dmr_fact_vehicle_id`, `price`, `km_driven`, and other slim-set fields are stored on the `vehicles` row
4. `equipment_ids` syncs `vehicle_equipment`
5. `images[]` become `vehicle_images` rows under the `vehicles` record

After creation:

1. `vehicle_list_status_id` is set to `published`
2. `published_at` is set to `now()`

## 5. Summary for Vue implementation

1. Use `dmr_fact_vehicle_id` as the only identity you persist and send on create/update.
2. Treat `address`/`postcode` as the location fields stored on `vehicles`.
3. For status changes, prefer `update-status` endpoint; don’t manually set `published_at` unless you have a strong reason.
4. For listing display, use returned computed fields like `brand_name`, `model_name`, `fuel_type_name`, `model_year_name`, `engine_power_hp`.
5. For filters, the public listing endpoints join through DMR tables; dealer list endpoints are mostly `search`, `vehicle_list_status_id`, and `sort`.

