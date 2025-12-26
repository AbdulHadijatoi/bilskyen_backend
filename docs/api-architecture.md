<!--
API Architecture Checksum: 9de8182014ce68a3001932b104bb4d3f31ca016c99a7c5f860b6c4bc90a4834b
Source: backend/docs/api-architecture.md
Algorithm: SHA-256

IMPORTANT: If this checksum changes, the API architecture has been modified.
Re-evaluate the entire API architecture and update this documentation accordingly.
Treat this architecture as immutable unless the checksum changes.
-->

# API Architecture Documentation

## Overview

This document describes the API architecture for the project backend. The API follows RESTful principles with standardized response formats, versioning, authentication, and error handling.

## Table of Contents

- [API Versioning](#api-versioning)
- [Route Structure](#route-structure)
- [Authentication](#authentication)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Idempotency](#idempotency)
- [Nummerplade API Integration](#nummerplade-api-integration)
- [Endpoints](#endpoints)
- [Middleware](#middleware)
- [Audit & Logging](#audit--logging)
- [Code Organization & Architecture](#code-organization--architecture)
- [Best Practices](#best-practices)
- [Infrastructure Requirements](#infrastructure-requirements)

## API Versioning

All API routes are prefixed with `/api/v1` to prevent breaking changes for mobile apps and external clients.

**Base URL:** `https://your-domain.com/api/v1`

**Example:**
```
GET /api/v1/vehicles
POST /api/v1/auth/login
GET /api/v1/dealer/vehicles
```

## Route Structure

Routes are organized into three main files:

1. **`routes/api.php`** - Public/common routes
2. **`routes/dealer-apis.php`** - Dealer-specific routes (prefixed with `/api/v1/dealer`)
3. **`routes/admin-apis.php`** - Admin-specific routes (prefixed with `/api/v1/admin`)

### Route Prefixes

- **Public Routes:** `/api/v1/*`
- **Dealer Routes:** `/api/v1/dealer/*`
- **Admin Routes:** `/api/v1/admin/*`
- **Nummerplade Proxy:** `/api/v1/nummerplade/*`

## Authentication

### Standardized Middleware

All protected routes use the standardized `auth:api` middleware, which uses JWT authentication.

**Middleware:** `auth:api`

**Example:**
```php
Route::middleware('auth:api')->group(function () {
    Route::get('/vehicles', [VehicleController::class, 'index']);
});
```

### JWT Authentication

- **Access Token:** Short-lived (default: 30 minutes)
- **Refresh Token:** Long-lived (14 days), stored as HttpOnly cookie
- **Token Type:** Bearer

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/auth/register` | Register new user | No |
| POST | `/api/v1/auth/login` | Login user | No |
| POST | `/api/v1/auth/refresh` | Refresh access token | No |
| POST | `/api/v1/auth/logout` | Logout user | Yes |
| GET | `/api/v1/auth/me` | Get current user | Yes |
| POST | `/api/v1/auth/sign-out` | Sign out | Yes |
| GET | `/api/v1/auth/get-session` | Get session | Yes |
| POST | `/api/v1/auth/update-user` | Update user profile | Yes |
| POST | `/api/v1/auth/revoke-session` | Revoke session | Yes |
| POST | `/api/v1/auth/change-password` | Change password | Yes |

## Response Format

### Success Response

**All success responses are consistently wrapped in a `data` object** for consistency:

```json
{
  "data": {
    "id": 1,
    "title": "Vehicle Name",
    "price": 100000,
    "category_id": 1,
    "category_name": "SUV",
    "brand_id": 2,
    "brand_name": "Toyota",
    "model_year_id": 3,
    "model_year_name": "2023",
    "fuel_type_id": 1,
    "fuel_type_name": "Petrol"
  }
}
```

**Note:** Vehicle responses automatically include resolved names for lookup fields (category_name, brand_name, model_year_name, fuel_type_name, vehicle_list_status_name) via model accessors. These are cached and do not require eager-loading relationships.

### Paginated Response

Paginated responses follow this structure:

```json
{
  "data": {
    "docs": [...],
    "limit": 15,
    "page": 1,
    "hasPrevPage": false,
    "hasNextPage": true,
    "prevPage": null,
    "nextPage": 2,
    "totalDocs": 50,
    "totalPages": 4
  }
}
```

**Note:** `totalDocs` and `totalPages` are optional for performance (can be skipped for complex queries).

### Error Response

Error responses follow this structure:

```json
{
  "status": "error",
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Metadata Response

For responses with metadata (feature flags, limits, app config):

```json
{
  "data": {...},
  "meta": {
    "feature_flags": {...},
    "limits": {...}
  }
}
```

## Error Handling

### HTTP Status Codes

Standard HTTP status codes are used:

- `200` - OK
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error
- `503` - Service Unavailable

### Nummerplade API Errors

Nummerplade API errors follow a standardized structure:

```json
{
  "status": "error",
  "message": "External vehicle data unavailable",
  "source": "nummerplade",
  "retryable": true,
  "code": "TIMEOUT"
}
```

**Error Codes:**
- `TIMEOUT` - Request timed out (retryable)
- `RATE_LIMIT` - Rate limit exceeded (retryable)
- `INVALID_INPUT` - Invalid registration or VIN (not retryable)
- `SERVICE_DOWN` - External service unavailable (retryable)
- `UNKNOWN` - Unknown error (not retryable)

## Rate Limiting

### Global Baseline

**120 requests per minute per IP** - Applied to all API routes by default.

### Stricter Overrides

Specific endpoints have stricter rate limits:

| Endpoint | Rate Limit |
|----------|------------|
| `POST /api/v1/auth/login` | 10 requests/minute |
| `POST /api/v1/auth/register` | 6 requests/minute |
| `POST /api/v1/auth/refresh` | 20 requests/minute |
| `POST /api/v1/dealer/vehicles` | 20 requests/minute per user |
| `POST /api/v1/nummerplade/vehicle-by-*` | 40 requests/minute per IP |
| `GET /api/v1/nummerplade/inspections/*` | 20 requests/minute per IP |

### Rate Limit Headers

When rate limited, the API returns:

```
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0
Retry-After: 60
```

## Idempotency

Critical POST endpoints support idempotency to prevent duplicate resource creation.

### Usage

Include an `Idempotency-Key` header in your request:

```
POST /api/v1/dealer/vehicles
Idempotency-Key: unique-key-here
```

### Supported Endpoints

- `POST /api/v1/auth/register`
- `POST /api/v1/dealer/vehicles`
- `POST /api/v1/admin/users`
- `POST /api/v1/admin/dealers`
- `POST /api/v1/admin/plans`

### Behavior

- Idempotency keys are stored in Redis with a 24-hour TTL
- If a request with the same key is made within 24 hours, the cached response is returned
- Only successful responses (2xx) are cached

## Nummerplade API Integration

### Overview

The Nummerplade API is integrated to fetch vehicle data when creating listings. Data is fetched once during creation and stored in the database for future use.

### Data Flow

1. **Listing Creation:**
   - User provides `registration` (license plate) or `vin`
   - System fetches data from Nummerplade API
   - Data is transformed and stored in database
   - User can override/modify any fields

2. **Search/Listing:**
   - Uses data from internal database (already fetched)
   - No external API calls needed

### Proxy Endpoints

Proxy endpoints are available for Flutter/Vue.js clients:

| Method | Endpoint | Description | Rate Limit |
|--------|----------|-------------|------------|
| POST | `/api/v1/nummerplade/vehicle-by-registration` | Get vehicle by registration | 40/min |
| POST | `/api/v1/nummerplade/vehicle-by-vin` | Get vehicle by VIN | 40/min |
| GET | `/api/v1/nummerplade/reference/body-types` | Get body types (cached) | - |
| GET | `/api/v1/nummerplade/reference/colors` | Get colors (cached) | - |
| GET | `/api/v1/nummerplade/reference/fuel-types` | Get fuel types (cached) | - |
| GET | `/api/v1/nummerplade/reference/equipment` | Get equipment (cached) | - |
| GET | `/api/v1/nummerplade/reference/permits` | Get permits (cached) | - |
| GET | `/api/v1/nummerplade/reference/types` | Get types (cached) | - |
| GET | `/api/v1/nummerplade/reference/uses` | Get vehicle uses (cached) | - |
| GET | `/api/v1/nummerplade/inspections/{vehicleId}` | Get inspections | 20/min |
| GET | `/api/v1/nummerplade/dmr/{vehicleId}` | Get DMR data | 20/min |
| GET | `/api/v1/nummerplade/debt/{vehicleId}` | Get debt data | 20/min |
| GET | `/api/v1/nummerplade/tinglysning/{vin}` | Get tinglysning data | 20/min |
| GET | `/api/v1/nummerplade/emissions/{input}` | Get emissions | 20/min |
| GET | `/api/v1/nummerplade/evaluations/{input}` | Get evaluations | 20/min |

## Endpoints

### Public Endpoints

#### Vehicles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vehicles` | List published vehicles |
| GET | `/api/v1/vehicles/{id}` | Get vehicle details |

#### Lookup Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/locations` | Get locations |
| GET | `/api/v1/fuel-types` | Get fuel types |
| GET | `/api/v1/transmissions` | Get transmission types |
| GET | `/api/v1/categories` | Get vehicle categories |
| GET | `/api/v1/brands` | Get vehicle brands |
| GET | `/api/v1/model-years` | Get model years |

### Dealer Endpoints

All dealer endpoints require `auth:api` middleware and are prefixed with `/api/v1/dealer`.

#### Vehicles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dealer/vehicles` | List dealer's vehicles |
| GET | `/api/v1/dealer/vehicles/{id}` | Get vehicle details |
| POST | `/api/v1/dealer/vehicles` | Create vehicle (with idempotency) |
| PUT | `/api/v1/dealer/vehicles/{id}` | Update vehicle |
| DELETE | `/api/v1/dealer/vehicles/{id}` | Soft delete vehicle |
| POST | `/api/v1/dealer/vehicles/{id}/images` | Upload vehicle images |
| DELETE | `/api/v1/dealer/vehicles/{id}/images/{imageId}` | Delete vehicle image |
| PUT | `/api/v1/dealer/vehicles/{id}/status` | Update vehicle status |
| PUT | `/api/v1/dealer/vehicles/{id}/price` | Update price (creates history) |
| POST | `/api/v1/dealer/vehicles/fetch-from-nummerplade` | Fetch from Nummerplade API |

**Query Parameters for `/api/v1/dealer/vehicles`:**
- `search` - Search in title, registration, VIN
- `category_id` - Filter by category
- `brand_id` - Filter by brand
- `model_year_id` - Filter by model year
- `fuel_type_id` - Filter by fuel type
- `min_price` - Minimum price filter
- `max_price` - Maximum price filter
- `limit` - Results per page (default: 15)
- `page` - Page number
- `with_deleted` - Include soft-deleted records (default: false)

**Vehicle Creation/Update Fields:**
- `title` (required) - Vehicle title
- `registration` (optional) - License plate
- `vin` (optional) - Vehicle Identification Number
- `category_id` (optional) - Category ID
- `location_id` (required) - Location ID
- `brand_id` (optional) - Brand ID
- `model_year_id` (optional) - Model year ID
- `km_driven` (optional) - Kilometers driven
- `fuel_type_id` (required) - Fuel type ID
- `price` (required) - Price in DKK
- `mileage` (optional) - Odometer reading
- `battery_capacity` (optional) - Battery capacity
- `engine_power` (optional) - Engine power
- `towing_weight` (optional) - Towing weight
- `ownership_tax` (optional) - Ownership tax
- `first_registration_date` (optional) - First registration date
- `vehicle_list_status_id` (required) - Vehicle status ID
- `published_at` (optional) - Publication timestamp
- `description` (optional) - Vehicle description (stored in vehicle_details)
- Additional vehicle_details fields can be included in the same request

#### Leads

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dealer/leads` | List dealer's leads |
| GET | `/api/v1/dealer/leads/{id}` | Get lead details |
| POST | `/api/v1/dealer/leads/{id}/assign` | Assign lead to staff |
| PUT | `/api/v1/dealer/leads/{id}/stage` | Update lead stage |
| GET | `/api/v1/dealer/leads/{id}/messages` | Get chat messages |
| POST | `/api/v1/dealer/leads/{id}/messages` | Send message |

#### Favorites & Saved Searches

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dealer/favorites` | Get user's favorites |
| POST | `/api/v1/dealer/favorites` | Add favorite |
| DELETE | `/api/v1/dealer/favorites/{vehicleId}` | Remove favorite |
| GET | `/api/v1/dealer/saved-searches` | Get saved searches |
| POST | `/api/v1/dealer/saved-searches` | Save search |
| DELETE | `/api/v1/dealer/saved-searches/{id}` | Delete saved search |

#### Dealer Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dealer/profile` | Get dealer info |
| PUT | `/api/v1/dealer/profile` | Update dealer info |
| GET | `/api/v1/dealer/staff` | List dealer staff |
| POST | `/api/v1/dealer/staff` | Add staff member |
| PUT | `/api/v1/dealer/staff/{userId}` | Update staff role |
| DELETE | `/api/v1/dealer/staff/{userId}` | Remove staff |

#### Subscriptions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dealer/subscription` | Get current subscription |
| GET | `/api/v1/dealer/subscription/features` | Get available features |
| GET | `/api/v1/dealer/subscription/history` | Get subscription history |

### Admin Endpoints

All admin endpoints require `auth:api` and `role:admin` middleware and are prefixed with `/api/v1/admin`.

#### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/users` | List all users |
| GET | `/api/v1/admin/users/{id}` | Get user details |
| POST | `/api/v1/admin/users` | Create user (with idempotency) |
| PUT | `/api/v1/admin/users/{id}` | Update user |
| DELETE | `/api/v1/admin/users/{id}` | Soft delete user |
| PUT | `/api/v1/admin/users/{id}/status` | Update user status |
| PUT | `/api/v1/admin/users/{id}/ban` | Ban user |
| PUT | `/api/v1/admin/users/{id}/unban` | Unban user |

#### Dealers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/dealers` | List all dealers |
| GET | `/api/v1/admin/dealers/{id}` | Get dealer details |
| POST | `/api/v1/admin/dealers` | Create dealer (with idempotency) |
| PUT | `/api/v1/admin/dealers/{id}` | Update dealer |
| DELETE | `/api/v1/admin/dealers/{id}` | Soft delete dealer |

#### Vehicles (Admin can see all dealer listings)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/vehicles` | List all vehicles (any dealer) |
| GET | `/api/v1/admin/vehicles/{id}` | Get vehicle details |
| PUT | `/api/v1/admin/vehicles/{id}/status` | Update vehicle status |
| DELETE | `/api/v1/admin/vehicles/{id}` | Soft delete vehicle |
| GET | `/api/v1/admin/vehicles/{id}/history` | Get vehicle history |

#### Plans & Subscriptions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/plans` | List all plans |
| GET | `/api/v1/admin/plans/{id}` | Get plan details |
| POST | `/api/v1/admin/plans` | Create plan (with idempotency) |
| PUT | `/api/v1/admin/plans/{id}` | Update plan |
| DELETE | `/api/v1/admin/plans/{id}` | Soft delete plan |
| GET | `/api/v1/admin/plans/{id}/features` | Get plan features |
| POST | `/api/v1/admin/plans/{id}/features` | Assign feature to plan |
| DELETE | `/api/v1/admin/plans/{id}/features/{featureId}` | Remove feature |
| GET | `/api/v1/admin/subscriptions` | List all subscriptions |
| POST | `/api/v1/admin/subscriptions` | Create subscription |
| PUT | `/api/v1/admin/subscriptions/{id}/status` | Update subscription status |

#### Features

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/features` | List all features |
| GET | `/api/v1/admin/features/{id}` | Get feature details |
| POST | `/api/v1/admin/features` | Create feature |
| PUT | `/api/v1/admin/features/{id}` | Update feature |
| DELETE | `/api/v1/admin/features/{id}` | Delete feature |

#### CMS

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/pages` | List all pages |
| GET | `/api/v1/admin/pages/{id}` | Get page details |
| POST | `/api/v1/admin/pages` | Create page |
| PUT | `/api/v1/admin/pages/{id}` | Update page |
| DELETE | `/api/v1/admin/pages/{id}` | Delete page |
| PUT | `/api/v1/admin/pages/{id}/publish` | Publish page |
| GET | `/api/v1/admin/blogs` | List all blogs |
| GET | `/api/v1/admin/blogs/{id}` | Get blog details |
| POST | `/api/v1/admin/blogs` | Create blog |
| PUT | `/api/v1/admin/blogs/{id}` | Update blog |
| DELETE | `/api/v1/admin/blogs/{id}` | Delete blog |

#### Analytics & Audit

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/analytics/vehicles` | Vehicle analytics |
| GET | `/api/v1/admin/analytics/leads` | Lead analytics |
| GET | `/api/v1/admin/analytics/subscriptions` | Subscription analytics |
| GET | `/api/v1/admin/audit-logs` | Get audit logs |

## Middleware

### Available Middleware

| Middleware | Description |
|------------|-------------|
| `auth:api` | JWT authentication (standardized) |
| `role:admin` | Admin role check |
| `permission:resource,action` | Permission check |
| `idempotency` | Idempotency key handling |
| `throttle:limit,minutes` | Rate limiting |

### Permission Matrix

The `permission:resource,action` middleware enforces fine-grained access control. Below is the permission matrix:

#### Dealer Permissions

| Resource | Actions | Description |
|----------|---------|-------------|
| `vehicles` | `create`, `read`, `update`, `delete` | Manage own vehicles |
| `leads` | `read`, `update`, `assign` | Manage dealer leads |
| `staff` | `read`, `create`, `update`, `delete` | Manage dealer staff |
| `profile` | `read`, `update` | Manage dealer profile |
| `subscription` | `read` | View subscription details |
| `favorites` | `read`, `create`, `delete` | Manage favorites |
| `saved-searches` | `read`, `create`, `delete` | Manage saved searches |

#### Admin Permissions

| Resource | Actions | Description |
|----------|---------|-------------|
| `users` | `create`, `read`, `update`, `delete`, `ban`, `unban` | Full user management |
| `dealers` | `create`, `read`, `update`, `delete` | Full dealer management |
| `vehicles` | `read`, `update`, `delete` | View and manage all vehicles |
| `plans` | `create`, `read`, `update`, `delete` | Manage subscription plans |
| `features` | `create`, `read`, `update`, `delete` | Manage feature flags |
| `subscriptions` | `read`, `create`, `update` | Manage all subscriptions |
| `pages` | `create`, `read`, `update`, `delete`, `publish` | CMS page management |
| `blogs` | `create`, `read`, `update`, `delete` | CMS blog management |
| `analytics` | `read` | View analytics |
| `audit-logs` | `read` | View audit logs |

### Middleware Usage

```php
// Single middleware
Route::middleware('auth:api')->group(function () {
    // Routes
});

// Multiple middleware
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Routes
});

// Permission-based access
Route::post('/vehicles', [VehicleController::class, 'store'])
    ->middleware(['auth:api', 'permission:vehicles,create']);

// Rate limiting
Route::post('/vehicles', [VehicleController::class, 'store'])
    ->middleware(['throttle:10,1', 'idempotency']);
```

## Code Organization & Architecture

As the API grows, maintainability becomes critical. Follow these architectural patterns:

### Feature-Based Controllers

Organize controllers by feature domain rather than by resource type:

```
app/Http/Controllers/
├── Vehicles/
│   ├── VehicleController.php
│   ├── VehicleImageController.php
│   └── VehicleStatusController.php
├── Leads/
│   ├── LeadController.php
│   └── LeadMessageController.php
└── Admin/
    ├── UserManagementController.php
    └── DealerManagementController.php
```

### Thin Controllers + Service Classes

Controllers should be thin and delegate business logic to service classes:

```php
// Controller (thin)
class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService
    ) {}

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = $this->vehicleService->createVehicle(
            $request->validated(),
            $request->user()
        );
        
        return response()->json(['data' => $vehicle], 201);
    }
}

// Service (business logic)
class VehicleService
{
    public function createVehicle(array $data, User $user): Vehicle
    {
        // Business logic here
        // - Fetch from Nummerplade API
        // - Transform data
        // - Store in database
        // - Log audit entry
        // - Dispatch notifications
    }
}
```

### DTOs and Request Objects

Use Form Request classes for validation and DTOs for data transfer:

```php
// Form Request (validation)
class StoreVehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'registration' => 'required|string|max:10',
            'price' => 'required|numeric|min:0',
            // ...
        ];
    }
}

// DTO (data transfer)
class VehicleData
{
    public function __construct(
        public readonly string $registration,
        public readonly float $price,
        // ...
    ) {}
}
```

### Service Layer Structure

```
app/Services/
├── VehicleService.php
├── LeadService.php
├── Nummerplade/
│   └── NummerpladeService.php
└── Audit/
    └── AuditService.php
```

### Benefits

- **Testability:** Services can be unit tested independently
- **Reusability:** Business logic can be reused across controllers/commands
- **Maintainability:** Clear separation of concerns
- **Scalability:** Easy to add new features without bloating controllers

## Best Practices

### Request Headers

Always include these headers:

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

For idempotent requests:

```
Idempotency-Key: {unique-key}
```

### Error Handling

1. Always check HTTP status codes
2. Parse error responses for validation errors
3. Handle rate limiting (429) with retry logic
4. Handle Nummerplade API errors with retry logic for retryable errors

### Pagination

Always use pagination for list endpoints:

```
GET /api/v1/vehicles?limit=15&page=1
```

### Filtering

Use query parameters for filtering:

```
GET /api/v1/vehicles?category_id=1&brand_id=2&model_year_id=3&fuel_type_id=1&min_price=50000&max_price=100000
```

**Available Filters:**
- `category_id` - Filter by vehicle category
- `brand_id` - Filter by vehicle brand
- `model_year_id` - Filter by model year
- `fuel_type_id` - Filter by fuel type
- `min_price` - Minimum price
- `max_price` - Maximum price
- `search` - Text search in title, registration, VIN

### Sorting

Use query parameters for sorting:

```
GET /api/v1/vehicles?sort=price:asc
```

### Soft Deletes

Deleted resources are soft-deleted and can be restored. Use `withTrashed()` to include deleted records in queries.

**Important:** All list endpoints **exclude deleted records by default**. To include deleted records, use the `with_deleted=true` query parameter:

```
GET /api/v1/dealer/vehicles?with_deleted=true
```

Admin endpoints may include deleted records by default or via query parameter, depending on the endpoint. Check individual endpoint documentation for details.

### Vehicle Status Management

Use the single status endpoint instead of separate publish/unpublish endpoints:

```
PUT /api/v1/dealer/vehicles/{id}/status
{
  "status": "published" | "unpublished" | "archived" | "draft"
}
```

### Vehicle Data Caching

Vehicle lookup data (categories, brands, model_years, fuel_types, vehicle_list_statuses) is cached using a two-tier approach:

1. **Static Property Cache**: In-memory cache for the current request
2. **Laravel Cache Facade**: Persistent cache with 24-hour TTL (falls back to database if cache miss)

**Benefits:**
- Reduced database queries for constant/reference data
- Automatic resolution of lookup names via model accessors
- No need to eager-load constant relations in controllers
- Resolved names automatically appended to API responses

**Example Response:**
```json
{
  "data": {
    "id": 1,
    "title": "2023 Toyota Camry",
    "category_id": 1,
    "category_name": "Sedan",
    "brand_id": 2,
    "brand_name": "Toyota",
    "model_year_id": 3,
    "model_year_name": "2023",
    "fuel_type_id": 1,
    "fuel_type_name": "Petrol"
  }
}
```

### Default Ordering

All Vehicle queries default to `ORDER BY id DESC` via a global scope. This can be overridden by explicitly calling `orderBy()` in the query.

### Enum Validation

All enum-like inputs are validated at the API boundary using centralized enum definitions:

- Vehicle status: `draft`, `published`, `sold`, `archived`
- User status: `active`, `inactive`, `suspended`
- Subscription status: `trial`, `active`, `expired`, `canceled`, `scheduled`

## Audit & Logging

### Automatic Audit Logging

Critical actions are **automatically logged** to the audit log system. The following actions trigger audit log entries:

#### Vehicle Actions
- Price changes (via `PUT /api/v1/dealer/vehicles/{id}/price`)
- Status changes (via `PUT /api/v1/dealer/vehicles/{id}/status` or admin equivalent)
- Vehicle creation, update, deletion

#### User Management Actions
- User bans/unbans (via `PUT /api/v1/admin/users/{id}/ban` or `/unban`)
- User status changes (via `PUT /api/v1/admin/users/{id}/status`)
- User creation, update, deletion

#### Subscription Actions
- Subscription status changes
- Plan changes
- Feature assignments

#### Dealer Actions
- Dealer creation, update, deletion
- Staff role changes

### Audit Log Format

Each audit log entry includes:
- **User ID** - Who performed the action
- **Action** - What was done (e.g., `vehicle.price.updated`, `user.banned`)
- **Resource Type** - What resource was affected (e.g., `Vehicle`, `User`)
- **Resource ID** - Which specific resource
- **Changes** - Before/after values for changed fields
- **Timestamp** - When the action occurred
- **IP Address** - Request origin
- **User Agent** - Client information

### Accessing Audit Logs

Admin users can access audit logs via:
```
GET /api/v1/admin/audit-logs
```

Filters available:
- `user_id` - Filter by user
- `resource_type` - Filter by resource type
- `action` - Filter by action type
- `date_from` / `date_to` - Date range

## Infrastructure Requirements

### Redis

Redis is **mandatory** for:
- Idempotency key storage
- Rate limiting counters
- Nummerplade reference data caching
- JWT token blacklisting (for logout/revocation)

**Fail-Fast Behavior:**

The application **fails fast with a clear error** if Redis is unavailable for critical operations:

- **Authentication/Authorization:** Returns `503 Service Unavailable` with message: "Authentication service temporarily unavailable"
- **Idempotency:** Returns `503 Service Unavailable` with message: "Request processing temporarily unavailable"
- **Rate Limiting:** Falls back to in-memory rate limiting (per-request basis) with a warning log

**Health Check:**

The application should expose a health check endpoint that verifies Redis connectivity:
```
GET /health
```

Returns `200 OK` if Redis is available, `503 Service Unavailable` if not.

### Database

- MySQL/PostgreSQL for primary data storage
- Soft deletes enabled for critical entities
- Foreign key constraints enforced

## Version History

- **v1** - Initial API version (current)

## Support

For API support, please contact the development team or refer to the main project documentation.

