<!--
Schema Checksum: b18e0ba9260d813b9a4d50aab3c45042aba351464b282faa390f2ca634683537
Source: database-architecture.md
Algorithm: SHA-256

IMPORTANT: If this checksum changes, the database architecture has been modified.
Re-evaluate the entire database architecture and update this documentation accordingly.
Treat this architecture as immutable unless the checksum changes.
-->

# Database Architecture Documentation

## Overview

This document describes the database architecture for the Denmark Marketplace application. The system is designed as a dealer-based vehicle marketplace focused on the Danish market, with subscription management, lead tracking, and CMS capabilities.

### Key Design Principles

- **Denmark-specific**: Country code defaults to 'DK', currency defaults to 'DKK'
- **Dealer-centric**: Multi-tenant architecture with dealer-based organization
- **Subscription-based**: Flexible subscription system with feature flags and overrides
- **Immutable audit trail**: Price history and lead stage changes are logged
- **Optimized for search**: Indexes on key search fields for vehicle listings

## Database Structure

### Table Categories

1. **Users & Authentication**
2. **Dealers & Staff**
3. **Locations**
4. **Vehicles & Listings**
5. **User Features**
6. **Leads & Communication**
7. **CMS**
8. **Subscriptions & Plans**
9. **Analytics & Logging**

---

## Tables Reference

### Users & Authentication

#### `users`
Core user table for all system users (buyers, dealers, admins).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| name | VARCHAR(150) | User's full name |
| email | VARCHAR(150) | Unique email address |
| phone | VARCHAR(30) (NULL) | Phone number |
| whatsapp_number | VARCHAR(30) (NULL) | WhatsApp number (falls back to phone if null) |
| address | TEXT (NULL) | Street address |
| postcode | VARCHAR(10) (NULL) | Postal code |
| password | VARCHAR(255) | Hashed password |
| status_id | INT (FK, NULL) | Foreign key to `user_statuses.id` |
| email_verified_at | DATETIME (NULL) | Email verification timestamp |
| remember_token | VARCHAR(100) (NULL) | Remember me token |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |
| deleted_at | DATETIME (NULL) | Soft delete timestamp |

**Indexes:**
- `email` (unique)
- `status_id`

**Foreign Keys:**
- `status_id` references `user_statuses.id` (nullOnDelete)

**Model Features:**
- **Soft Deletes**: Enabled for data retention
- **JWT Subject**: Implements JWTSubject interface for authentication
- **Roles & Permissions**: Uses Spatie Permission package (guard: 'web')
- **Accessors**: 
  - `initials` - Generates initials from user name (first letter of first name + first letter of last name, or first 2 characters if single word)

**Relationships:**
- `belongsTo` UserStatus
- `hasMany` Dealer (owned dealers via `user_id`)
- `hasMany` DealerStaff (staff memberships)
- `hasMany` Vehicle, Favorite, SavedSearch, Lead (buyer/assigned), Enquiry, ChatMessage, PriceHistory (changed_by), ListingViewsLog, UserPlanOverride

#### `user_statuses`
Lookup table for user status values.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Status name (Active, Inactive, Suspended) |

**Constants (Model):**
- `ACTIVE = 1`
- `INACTIVE = 2`
- `SUSPENDED = 3`

---

### Dealers & Staff

#### `dealers`
Dealer companies registered in the system.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| user_id | BIGINT (FK) | Foreign key to `users.id` (dealer owner, required) |
| cvr | VARCHAR(20) | Danish CVR number (unique) |
| address | TEXT | Street address |
| city | VARCHAR(100) | City name |
| postcode | VARCHAR(10) | Postal code |
| country_code | CHAR(2) | Country code (default: 'DK') |
| logo_path | VARCHAR(255) (NULL) | Logo file path |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |
| deleted_at | DATETIME (NULL) | Soft delete timestamp |

**Indexes:**
- `cvr` (unique)
- `postcode`
- `user_id`

**Foreign Keys:**
- `user_id` references `users.id` (cascadeOnDelete)

**Model Features:**
- **Soft Deletes**: Enabled for data retention

**Accessors (Model):**
- `logo_url` - Full URL to logo image (returns `asset('storage/' . logo_path)` or null)

**Relationships:**
- `belongsTo` User (owner via `user_id`)
- `hasMany` DealerStaff, Vehicle, Lead, DealerSubscription, DealerPlanOverride

#### `dealer_staff`
Table linking staff members to dealers with auto-generated usernames.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| username | VARCHAR(150) | Auto-generated username (unique, format: staff_{dealer_id}_{sequential}) |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Constraints:**
- Unique constraint on `(dealer_id, user_id)`
- Unique constraint on `username`

**Indexes:**
- `username` (unique)
- `dealer_id`
- `user_id`

**Relationships:**
- `belongsTo` Dealer, User

---

### Locations

#### `locations`
Denmark location data with geographic coordinates.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| city | VARCHAR(100) | City name |
| postcode | VARCHAR(10) | Postal code |
| region | VARCHAR(100) | Region/state name |
| country_code | CHAR(2) | Country code (default: 'DK') |
| latitude | DECIMAL(10,7) | Latitude coordinate |
| longitude | DECIMAL(10,7) | Longitude coordinate |

**Indexes:**
- `postcode`
- `city`
- `(latitude, longitude)` - For geo queries

**Relationships:**
- `hasMany` Vehicle

---

### Vehicles & Listings

#### `vehicles`
Vehicle listings with searchable attributes.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| title | VARCHAR(255) (NULL) | Listing title (nullable, auto-generated from brand + model + model year if empty) |
| registration | VARCHAR(20) (NULL) | License plate number |
| vin | VARCHAR(17) (NULL) | Vehicle Identification Number |
| dealer_id | BIGINT (FK, NULL) | Foreign key to `dealers.id` (nullable for private sellers) |
| user_id | BIGINT (FK) | Foreign key to `users.id` (creator) |
| category_id | INT (FK, NULL) | Foreign key to `categories.id` |
| brand_id | INT (FK, NULL) | Foreign key to `brands.id` |
| model_id | INT (FK, NULL) | Foreign key to `models.id` |
| model_year_id | INT (FK, NULL) | Foreign key to `model_years.id` |
| km_driven | INT (NULL) | Kilometers driven |
| fuel_type_id | INT (FK) | Foreign key to `fuel_types.id` |
| price | INT | Price in DKK |
| battery_capacity | INT (NULL) | Battery capacity (for electric vehicles) |
| range_km | INT (NULL) | Electric range in kilometers (for electric vehicles) |
| charging_type | VARCHAR(100) (NULL) | Charging type (AC, DC, AC/DC) |
| engine_power | INT (NULL) | Engine power (in kW) |
| towing_weight | INT (NULL) | Towing weight capacity |
| ownership_tax | INT (NULL) | Ownership tax amount |
| first_registration_date | DATE (NULL) | First registration date |
| address | VARCHAR (NULL) | Seller/dealer street address |
| postcode | VARCHAR(20) (NULL) | Seller/dealer postcode |
| latitude | DECIMAL(10,7) (NULL) | Street-level WGS84 latitude (DAWA) |
| longitude | DECIMAL(10,7) (NULL) | Street-level WGS84 longitude (DAWA) |
| version | VARCHAR(100) (NULL) | Vehicle version (moved from vehicle_details) |
| gear_type_id | INT (FK, NULL) | Foreign key to `gear_types.id` (moved from vehicle_details) |
| fuel_efficiency | DECIMAL(8,2) (NULL) | Fuel efficiency (km/l) (moved from vehicle_details) |
| vehicle_list_status_id | INT (FK) | Foreign key to `vehicle_list_statuses.id` |
| listing_type_id | INT (FK, NULL) | Foreign key to `listing_types.id` (Purchase/Leasing) |
| published_at | DATETIME (NULL) | Publication timestamp |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |
| deleted_at | DATETIME (NULL) | Soft delete timestamp |

**Indexes:**
- `registration` - For Nummerplade API lookups
- `vin` - For Nummerplade API lookups
- `(vehicle_list_status_id, published_at)` - For active listings
- `(vehicle_list_status_id, price)` - For price sorting
- `category_id` - For category filtering
- `brand_id` - For brand filtering
- `model_id` - For model filtering
- `model_year_id` - For model year filtering
- `model_year` - For calendar year range filters and sorts
- `km_driven` - For mileage range filters and sorts
- `(latitude, longitude)` - For street-level radius filter and map pins
- `listing_type_id` - For listing type filtering
- `gear_type_id` - For gear type filtering

**Foreign Keys:**
- `dealer_id` references `dealers.id` (nullOnDelete)
- `user_id` references `users.id` (cascadeOnDelete)
- `category_id` references `categories.id` (nullOnDelete)
- `brand_id` references `brands.id` (nullOnDelete)
- `model_id` references `models.id` (nullOnDelete)
- `model_year_id` references `model_years.id` (nullOnDelete)
- `fuel_type_id` references `fuel_types.id` (cascadeOnDelete)
- `gear_type_id` references `gear_types.id` (nullOnDelete)
- `vehicle_list_status_id` references `vehicle_list_statuses.id` (cascadeOnDelete)
- `listing_type_id` references `listing_types.id` (nullOnDelete)

**Relationships:**
- `belongsTo` Dealer (nullable), User, Brand, VehicleModel (model), ModelYear, ListingType, GearType
- `hasOne` VehicleDetail
- `hasMany` VehicleImage, Favorite, Lead, PriceHistory, ListingViewsLog, FeaturedListing
- `belongsToMany` Equipment (via vehicle_equipment)

**Model Features:**
- **Caching**: Lookup data (categories, brands, models, model_years, fuel_types, gear_types, vehicle_list_statuses, listing_types) is cached using static property + Laravel Cache facade (24-hour TTL)
- **Accessors**: 
  - Automatically appends resolved names (`category_name`, `brand_name`, `model_name`, `model_year_name`, `fuel_type_name`, `gear_type_name`, `vehicle_list_status_name`, `listing_type_name`) to API responses
  - `title` accessor auto-generates title from `brand_name + model_name + model_year_name` if the stored title is empty or null
  - `engine_power_hp` accessor converts `engine_power` (in kW) to horsepower using formula: `engine_power * 1.36` (rounded to 2 decimal places)
- **Default Ordering**: Global scope applies `ORDER BY id DESC` by default (can be overridden with explicit `orderBy`)
- **Soft Deletes**: Enabled for data retention

#### `vehicle_details`
Extended vehicle information and specifications.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK, UNIQUE) | Foreign key to `vehicles.id` |
| vehicle_external_id | VARCHAR(255) (NULL) | External vehicle identifier (e.g., from Nummerplade API) |
| description | TEXT (NULL) | Full vehicle description |
| views_count | INT | View counter (default: 0) |
| vin_location | VARCHAR(255) (NULL) | VIN location |
| type_id | INT (FK, NULL) | Foreign key to `types.id` |
| type_name | VARCHAR(255) (NULL) | Type name |
| registration_status | VARCHAR(100) (NULL) | Registration status |
| registration_status_updated_date | DATE (NULL) | Registration status update date |
| expire_date | DATE (NULL) | Registration expiration date |
| status_updated_date | DATE (NULL) | Status update date |
| total_weight | INT (NULL) | Total weight |
| vehicle_weight | INT (NULL) | Vehicle weight |
| technical_total_weight | INT (NULL) | Technical total weight |
| coupling | BOOLEAN (NULL) | Coupling (changed from INT to BOOLEAN) |
| towing_weight_brakes | INT (NULL) | Towing weight with brakes |
| minimum_weight | INT (NULL) | Minimum weight |
| gross_combination_weight | INT (NULL) | Gross combination weight |
| engine_displacement | INT (NULL) | Engine displacement |
| engine_cylinders | INT (NULL) | Number of engine cylinders |
| engine_code | VARCHAR(100) (NULL) | Engine code |
| category | VARCHAR(100) (NULL) | Category |
| last_inspection_date | DATE (NULL) | Last inspection date |
| last_inspection_result | VARCHAR(100) (NULL) | Last inspection result |
| last_inspection_odometer | INT (NULL) | Odometer reading at last inspection |
| type_approval_code | VARCHAR(100) (NULL) | Type approval code |
| top_speed | INT (NULL) | Top speed |
| doors | INT (NULL) | Number of doors |
| minimum_seats | INT (NULL) | Minimum seats |
| maximum_seats | INT (NULL) | Maximum seats |
| wheels | TEXT (NULL) | Number of wheels (changed from INT to TEXT) |
| extra_equipment | TEXT (NULL) | Extra equipment details |
| axles | INT (NULL) | Number of axles |
| drive_axles | INT (NULL) | Number of drive axles |
| wheelbase | INT (NULL) | Wheelbase measurement |
| leasing_period_start | DATE (NULL) | Leasing period start |
| leasing_period_end | DATE (NULL) | Leasing period end |
| use_id | INT (FK, NULL) | Foreign key to `uses.id` |
| color_id | INT (FK, NULL) | Foreign key to `colors.id` |
| body_type_id | INT (FK, NULL) | Foreign key to `body_types.id` |
| variant_id | INT (FK, NULL) | Foreign key to `variants.id` |
| dispensations | TEXT (NULL) | Dispensations |
| permits | TEXT (NULL) | Permits |
| ncap_five | BOOLEAN (NULL) | NCAP 5-star rating |
| airbags | INT (NULL) | Number of airbags |
| integrated_child_seats | INT (NULL) | Number of integrated child seats |
| seat_belt_alarms | INT (NULL) | Number of seat belt alarms |
| euronom_id | INT (FK, NULL) | Foreign key to `euronorms.id` (replaces euronorm string) |
| servicebog | ENUM('Yes', 'No', 'Default') (NULL) | Service book status |
| price_type_id | INT (FK, NULL) | Foreign key to `price_types.id` |
| condition_id | INT (FK, NULL) | Foreign key to `conditions.id` |
| sales_type_id | INT (FK, NULL) | Foreign key to `sales_types.id` |
| seller_phone | VARCHAR(30) (NULL) | Seller contact phone |
| seller_address | TEXT (NULL) | Seller contact address |
| seller_postcode | VARCHAR(10) (NULL) | Seller contact postcode |
| annual_tax | DECIMAL(10,2) (NULL) | Annual tax amount |
| owners | TEXT (NULL) | Vehicle owners information (stored as JSON array) |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `vehicle_id` (unique)
- `type_id`
- `use_id`
- `color_id`
- `body_type_id`
- `variant_id`
- `euronom_id`
- `price_type_id`
- `condition_id`
- `sales_type_id`

**Foreign Keys:**
- `vehicle_id` references `vehicles.id` (cascadeOnDelete)
- `type_id` references `types.id` (nullOnDelete)
- `use_id` references `uses.id` (nullOnDelete)
- `color_id` references `colors.id` (nullOnDelete)
- `body_type_id` references `body_types.id` (nullOnDelete)
- `variant_id` references `variants.id` (nullOnDelete)
- `euronom_id` references `euronorms.id` (nullOnDelete)
- `price_type_id` references `price_types.id` (nullOnDelete)
- `condition_id` references `conditions.id` (nullOnDelete)
- `sales_type_id` references `sales_types.id` (nullOnDelete)

**Model Features:**
- **Caching**: Lookup data (types, uses, colors, body_types, price_types, conditions, sales_types) is cached using static property + Laravel Cache facade (24-hour TTL)
- **Accessors**: Automatically appends resolved names (`type_name_resolved`, `use_name`, `color_name`, `body_type_name`, `price_type_name`, `condition_name`, `sales_type_name`) to API responses
- **Casts**: `owners` is cast to array, `coupling` is cast to boolean, `annual_tax` is cast to decimal
- **Note**: `version`, `gear_type_id`, and `fuel_efficiency` were moved to the `vehicles` table for optimization (reduces JOIN operations)
- No eager-loading of constant relations required

**Relationships:**
- `belongsTo` Vehicle, PriceType, Condition, SalesType, Variant, Euronom

#### `categories`
Vehicle category lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Category name |

**Note:** No timestamps. Used for caching in Vehicle model.

#### `brands`
Vehicle brand/manufacturer lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Brand name |

**Note:** No timestamps. Used for caching in Vehicle model.

**Relationships:**
- `hasMany` VehicleModel (models), Vehicle

#### `variants`
Vehicle variant lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Variant name |

**Note:** No timestamps. Used in VehicleDetail model.

#### `euronorms`
Euro norm lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Euro norm name |

**Note:** No timestamps. Table name is `euronorms` (plural). Used in VehicleDetail model.

**Model Features:**
- **Caching**: Uses CachedLookup trait

#### `equipment_types`
Equipment type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Equipment type name |

**Note:** No timestamps. Used to categorize equipment.

**Relationships:**
- `hasMany` Equipment

#### `model_years`
Vehicle model year lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Model year name |

**Note:** No timestamps. Used for caching in Vehicle model.

#### `body_types`
Vehicle body type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Body type name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `colors`
Vehicle color lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Color name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `equipments`
Vehicle equipment lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Equipment name |
| equipment_type_id | INT (FK, NULL) | Foreign key to `equipment_types.id` |

**Indexes:**
- `equipment_type_id`

**Foreign Keys:**
- `equipment_type_id` references `equipment_types.id` (nullOnDelete)

**Note:** No timestamps. Reference data for equipment options. Table name is `equipments` (plural).

**Relationships:**
- `belongsTo` EquipmentType
- `belongsToMany` Vehicle (via vehicle_equipment)

#### `permits`
Vehicle permit lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Permit name |

**Note:** No timestamps. Reference data for permit types.

#### `types`
Vehicle type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Type name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `uses`
Vehicle use lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Use name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `fuel_types`
Vehicle fuel type lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Fuel type name (Petrol, Diesel, Electric, etc.) |

#### `models`
Vehicle model lookup table (child of brands).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| brand_id | INT (FK) | Foreign key to `brands.id` |
| name | VARCHAR(100) | Model name |

**Note:** No timestamps. Used for caching in Vehicle model.

**Indexes:**
- `brand_id`

**Foreign Keys:**
- `brand_id` references `brands.id` (nullOnDelete)

**Relationships:**
- `belongsTo` Brand
- `hasMany` Vehicle

#### `price_types`
Price type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Price type name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `conditions`
Condition lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Condition name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `gear_types`
Gear/transmission type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Gear type name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `sales_types`
Sales type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Sales type name |

**Note:** No timestamps. Used for caching in VehicleDetail model.

#### `listing_types`
Listing type lookup table (Purchase/Leasing).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Listing type name (Purchase, Leasing) |

**Note:** No timestamps. Used for caching in Vehicle model.

#### `vehicle_list_statuses`
Vehicle listing status lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Status name |

**Constants (Model):**
- `DRAFT = 1`
- `PUBLISHED = 2`
- `SOLD = 3`
- `ARCHIVED = 4`

#### `vehicle_images`
Vehicle image gallery.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| image_path | VARCHAR(255) | Image file path |
| thumbnail_path | VARCHAR(255) (NULL) | Thumbnail file path (300x300px) |
| sort_order | INT | Display order (default: 0) |

**Indexes:**
- `vehicle_id`

**Model Features:**
- **Accessors**: `image_url` and `thumbnail_url` automatically generate full URLs
- **Thumbnail Fallback**: If thumbnail doesn't exist, `thumbnail_url` falls back to full image URL
- Thumbnails are automatically generated when images are uploaded (300x300px, maintaining aspect ratio)

**Note:** No timestamps. Images are ordered by `sort_order`.

**Accessors (Model):**
- `image_url` - Full URL to image

**Relationships:**
- `belongsTo` Vehicle

---

### User Features

#### `favorites`
User favorite vehicles.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| created_at | DATETIME | Creation timestamp |

**Constraints:**
- Unique constraint on `(user_id, vehicle_id)`

**Relationships:**
- `belongsTo` User, Vehicle

#### `saved_searches`
Saved search filters for users.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| filters | JSON | Search filter criteria (cast to array) |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `user_id` references `users.id` (cascadeOnDelete)

**Model Features:**
- **Casts**: `filters` is cast to array
- **No Timestamps**: Only has `created_at`, no `updated_at`

**Relationships:**
- `belongsTo` User

---

### Leads & Communication

#### `leads`
Lead management for vehicle inquiries.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| buyer_user_id | BIGINT (FK) | Foreign key to `users.id` (buyer) |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| assigned_user_id | BIGINT (FK, NULL) | Foreign key to `users.id` (assigned staff) |
| lead_stage_id | INT (FK) | Foreign key to `lead_stages.id` |
| lead_intent_id | INT (FK, NULL) | Foreign key to `lead_intents.id` (Low, Medium, High, Very High) |
| source_id | INT (FK) | Foreign key to `sources.id` |
| lead_category_id | INT (FK, NULL) | Foreign key to `lead_categories.id` |
| last_activity_at | DATETIME (NULL) | Last activity timestamp |
| created_at | DATETIME | Creation timestamp |

**Indexes:**
- `dealer_id`
- `lead_stage_id`
- `lead_intent_id`
- `lead_category_id`

**Foreign Keys:**
- `vehicle_id` references `vehicles.id` (cascadeOnDelete)
- `buyer_user_id` references `users.id` (cascadeOnDelete)
- `dealer_id` references `dealers.id` (cascadeOnDelete)
- `assigned_user_id` references `users.id` (nullOnDelete)
- `lead_stage_id` references `lead_stages.id` (cascadeOnDelete)
- `lead_intent_id` references `lead_intents.id` (nullOnDelete)
- `source_id` references `sources.id` (cascadeOnDelete)
- `lead_category_id` references `lead_categories.id` (nullOnDelete)

**Model Features:**
- **No Timestamps**: Only has `created_at`, no `updated_at`

**Relationships:**
- `belongsTo` Vehicle, User (buyer), Dealer, User (assigned), LeadStage, LeadIntent, Source, LeadCategory
- `hasMany` LeadStageHistory, ChatThread

#### `lead_stages`
Lead kanban stage lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Stage name |

**Constants (Model):**
- `NEW = 1`
- `CONTACTED = 2`
- `QUALIFIED = 3`
- `QUOTED = 4`
- `NEGOTIATING = 5`
- `WON = 6`
- `LOST = 7`

#### `lead_stage_history`
Audit trail for lead stage changes.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| lead_id | BIGINT (FK) | Foreign key to `leads.id` |
| from_stage_id | INT (NULL) | Previous stage ID (nullable for initial stage) |
| to_stage_id | INT | New stage ID |
| changed_by_user_id | BIGINT (FK) | Foreign key to `users.id` |
| changed_at | DATETIME | Change timestamp |

**Foreign Keys:**
- `lead_id` references `leads.id` (cascadeOnDelete)
- `changed_by_user_id` references `users.id` (cascadeOnDelete)

**Note:** No timestamps. Uses `changed_at` field.

**Relationships:**
- `belongsTo` Lead, User (changed by)

#### `transmissions`
Transmission type lookup table.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Transmission type name |

**Note:** No timestamps. Reference data for transmission types.

#### `sources`
Lead source lookup (website, mobile app, phone, referral, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Source name |

**Constants (Model):**
- `WEBSITE = 'Website'`
- `MOBILE_APP = 'Mobile App'`
- `PHONE = 'Phone'`
- `EMAIL = 'Email'`
- `REFERRAL = 'Referral'`
- `SOCIAL_MEDIA = 'Social Media'`
- `WALK_IN = 'Walk-in'`

**Note:** No timestamps. Reference data for lead sources. Source is automatically detected based on request type (API vs Web).

**Relationships:**
- `hasMany` Lead

#### `lead_categories`
Lead category lookup (enquiry form, WhatsApp clicked, email clicked, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(100) | Category name |
| description | TEXT (NULL) | Category description |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Constants (Model):**
- `PRICE_NEGOTIATION_REQUEST = 1`
- `FINANCING_REQUEST = 2`
- `WHATSAPP_CLICKED = 3`
- `EMAIL_CLICKED = 4`
- `ENQUIRY_FORM_SUBMISSION = 5`
- `PHONE_NUMBER_REVEALED = 6`
- `REQUEST_TEST_DRIVE = 7`

**Relationships:**
- `hasMany` Lead

#### `lead_intents`
Lead intent level lookup (low, medium, high, very high).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Intent name |

**Constants (Model):**
- `LOW = 1`
- `MEDIUM = 2`
- `HIGH = 3`
- `VERY_HIGH = 4`

**Note:** No timestamps. Intent levels are automatically assigned based on lead category.

**Relationships:**
- `hasMany` Lead

#### `enquiries`
Detailed enquiry messages from users.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| serial_no | INT (NULL) | Serial number |
| subject | VARCHAR(200) | Enquiry subject |
| message | TEXT | Enquiry message |
| type | VARCHAR(50) | Enquiry type (General, Test Drive, Price Enquiry, etc.) |
| status | VARCHAR(50) | Enquiry status (New, In Progress, etc.) |
| source | VARCHAR(50) | Source (Website, Mobile App, etc.) |
| contact_id | BIGINT (FK, NULL) | Foreign key to `contacts.id` |
| user_id | BIGINT (FK, NULL) | Foreign key to `users.id` |
| vehicle_id | BIGINT (FK, NULL) | Foreign key to `vehicles.id` |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `serial_no`
- `user_id`
- `vehicle_id`
- `contact_id`
- `status`
- `type`

**Foreign Keys:**
- `user_id` references `users.id` (nullOnDelete)
- `vehicle_id` references `vehicles.id` (nullOnDelete)
- `contact_id` references `contacts.id` (nullOnDelete, conditional - only if contacts table exists)

**Model Features:**
- **HasSerialNumber**: Uses HasSerialNumber trait for auto-generating serial numbers
- **Source Detection**: Source is automatically set based on request type (API = "Mobile App", Web = "Website")

**Relationships:**
- `belongsTo` User, Vehicle, Contact (nullable, conditional)

#### `vehicle_equipment`
Pivot table for vehicle-equipment many-to-many relationship.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| equipment_id | INT (FK) | Foreign key to `equipments.id` |

**Indexes:**
- `(vehicle_id, equipment_id)` (unique)
- `vehicle_id`
- `equipment_id`

**Foreign Keys:**
- `vehicle_id` references `vehicles.id` (cascadeOnDelete)
- `equipment_id` references `equipments.id` (cascadeOnDelete)

**Note:** No timestamps. Pivot table for Vehicle-Equipment many-to-many relationship.

#### `chat_threads`
Chat conversation threads linked to leads.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| lead_id | BIGINT (FK) | Foreign key to `leads.id` |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `lead_id` references `leads.id` (cascadeOnDelete)

**Note:** No timestamps. Only has `created_at`.

**Relationships:**
- `belongsTo` Lead
- `hasMany` ChatMessage

#### `chat_messages`
Chat messages within threads.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| thread_id | BIGINT (FK) | Foreign key to `chat_threads.id` |
| sender_id | BIGINT (FK) | Foreign key to `users.id` |
| message | TEXT | Message content |
| is_internal | BOOLEAN | Internal note flag (default: false) |
| created_at | DATETIME | Creation timestamp |

**Indexes:**
- `thread_id`

**Foreign Keys:**
- `thread_id` references `chat_threads.id` (cascadeOnDelete)
- `sender_id` references `users.id` (cascadeOnDelete)

**Note:** No timestamps. Only has `created_at`.

**Relationships:**
- `belongsTo` ChatThread, User (sender)

---

### CMS

#### `pages`
CMS pages for static content.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| title | VARCHAR(255) | Page title |
| slug | VARCHAR(255) | URL slug (unique) |
| content | LONGTEXT (NULL) | Page content |
| meta_title | VARCHAR(255) (NULL) | SEO meta title |
| meta_description | TEXT (NULL) | SEO meta description |
| page_status_id | INT (FK) | Foreign key to `page_statuses.id` |

**Foreign Keys:**
- `page_status_id` references `page_statuses.id` (cascadeOnDelete)

**Note:** No timestamps.

**Relationships:**
- `belongsTo` PageStatus

#### `page_statuses`
Page status lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Status name |

**Constants (Model):**
- `DRAFT = 1`
- `PUBLISHED = 2`

#### `blogs`
Blog posts.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| title | VARCHAR(255) | Post title |
| slug | VARCHAR(255) | URL slug (unique) |
| content | LONGTEXT | Post content |
| meta_title | VARCHAR(255) (NULL) | SEO meta title |
| meta_description | TEXT (NULL) | SEO meta description |
| published_at | DATETIME (NULL) | Publication timestamp |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `slug` (unique)

**Model Features:**
- **Casts**: `published_at` is cast to datetime

**Relationships:**
- None (standalone CMS content)

---

### Subscriptions & Plans

#### `plans`
Subscription plans.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| name | VARCHAR(100) | Plan name |
| slug | VARCHAR(100) | Plan slug (unique) |
| description | TEXT | Plan description |
| is_active | BOOLEAN | Active flag |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Relationships:**
- `hasMany` PlanPriceHistory, PlanFeature, DealerSubscription, PlanAvailability
- `belongsToMany` Feature (through PlanFeature)

#### `plan_price_history`
Historical pricing for plans.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| plan_id | BIGINT (FK) | Foreign key to `plans.id` |
| price | INT | Price in cents |
| currency | CHAR(3) | Currency code (default: 'DKK') |
| billing_cycle | ENUM | 'monthly' or 'yearly' |
| starts_at | DATETIME | Effective start date |
| ends_at | DATETIME (NULL) | Effective end date (nullable) |

**Foreign Keys:**
- `plan_id` references `plans.id` (cascadeOnDelete)

**Note:** No timestamps. Table name is `plan_price_history`.

**Relationships:**
- `belongsTo` Plan

#### `features`
Feature definitions for plans.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| key | VARCHAR(100) | Feature key (unique) |
| feature_value_type_id | INT (FK) | Foreign key to `feature_value_types.id` |
| description | VARCHAR(255) | Feature description |
| created_at | DATETIME | Creation timestamp |

**Relationships:**
- `belongsTo` FeatureValueType
- `belongsToMany` Plan (through PlanFeature)
- `hasMany` PlanFeature, UserPlanOverride, DealerPlanOverride

#### `feature_value_types`
Feature value type lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Type name |

**Constants (Model):**
- `BOOLEAN = 1`
- `NUMBER = 2`
- `TEXT = 3`

#### `plan_features`
Plan-to-feature mapping with values.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| plan_id | BIGINT (FK) | Foreign key to `plans.id` |
| feature_id | BIGINT (FK) | Foreign key to `features.id` |
| value | VARCHAR(100) | Feature value |

**Foreign Keys:**
- `plan_id` references `plans.id` (cascadeOnDelete)
- `feature_id` references `features.id` (cascadeOnDelete)

**Note:** No timestamps. Pivot table for Plan-Feature many-to-many relationship.

**Relationships:**
- `belongsTo` Plan, Feature

#### `dealer_subscriptions`
Dealer subscription records (immutable pattern).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| plan_id | BIGINT (FK) | Foreign key to `plans.id` |
| subscription_status_id | INT (FK) | Foreign key to `subscription_statuses.id` |
| starts_at | DATETIME | Subscription start date |
| ends_at | DATETIME (NULL) | Subscription end date (nullable) |
| auto_renew | BOOLEAN | Auto-renewal flag (default: false) |
| created_at | DATETIME | Creation timestamp |
| deleted_at | DATETIME (NULL) | Soft delete timestamp |

**Foreign Keys:**
- `dealer_id` references `dealers.id` (cascadeOnDelete)
- `plan_id` references `plans.id` (cascadeOnDelete)
- `subscription_status_id` references `subscription_statuses.id` (cascadeOnDelete)

**Model Features:**
- **Soft Deletes**: Enabled for data retention
- **No Timestamps**: Only has `created_at`, no `updated_at`

**Important:** This table follows an immutable pattern. Upgrades/downgrades create new rows; existing rows are never updated.

**Relationships:**
- `belongsTo` Dealer, Plan, SubscriptionStatus

#### `subscription_statuses`
Subscription status lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Status name |

**Constants (Model):**
- `TRIAL = 1`
- `ACTIVE = 2`
- `EXPIRED = 3`
- `CANCELED = 4`
- `SCHEDULED = 5`

#### `plan_availability`
Plan availability rules by role.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| plan_id | BIGINT (FK) | Foreign key to `plans.id` |
| allowed_role_id | BIGINT (FK, NULL) | Foreign key to `roles.id` (Spatie Permission) |
| is_enabled | BOOLEAN | Availability flag (default: true) |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `plan_id` references `plans.id` (cascadeOnDelete)
- `allowed_role_id` references `roles.id` (Spatie Permission, nullOnDelete)

**Note:** No timestamps. Only has `created_at`. Table name is `plan_availability`.

**Relationships:**
- `belongsTo` Plan, Role (Spatie Permission, nullable)

#### `user_plan_overrides`
User-level feature overrides.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| feature_id | BIGINT (FK) | Foreign key to `features.id` |
| override_value | VARCHAR(100) | Override value |
| expires_at | DATETIME (NULL) | Expiration date (nullable) |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `user_id` references `users.id` (cascadeOnDelete)
- `feature_id` references `features.id` (cascadeOnDelete)

**Note:** No timestamps. Only has `created_at`.

**Relationships:**
- `belongsTo` User, Feature

#### `dealer_plan_overrides`
Dealer-level feature overrides.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| feature_id | BIGINT (FK) | Foreign key to `features.id` |
| override_value | VARCHAR(100) | Override value |
| expires_at | DATETIME (NULL) | Expiration date (nullable) |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `dealer_id` references `dealers.id` (cascadeOnDelete)
- `feature_id` references `features.id` (cascadeOnDelete)

**Note:** No timestamps. Only has `created_at`.

**Relationships:**
- `belongsTo` Dealer, Feature

---

### Analytics & Logging


#### `listing_views_log`
Vehicle listing view tracking.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| user_id | BIGINT (FK, NULL) | Foreign key to `users.id` (if logged in) |
| ip_address | VARCHAR(45) | Visitor IP address |
| user_agent | TEXT | Browser user agent |
| viewed_at | DATETIME | View timestamp |

**Indexes:**
- `(vehicle_id, viewed_at)`
- `(user_id, viewed_at)` — recently viewed rail for signed-in users

**Foreign Keys:**
- `vehicle_id` references `vehicles.id` (cascadeOnDelete)
- `user_id` references `users.id` (nullOnDelete)

**Note:** No timestamps. Uses `viewed_at` field. Table name is `listing_views_log`.

**Relationships:**
- `belongsTo` Vehicle, User (if logged in, nullable)

#### `price_history`
Vehicle price change audit trail.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK) | Foreign key to `vehicles.id` |
| old_price | INT | Previous price |
| new_price | INT | New price |
| changed_by_user_id | BIGINT (FK) | Foreign key to `users.id` |
| changed_at | DATETIME | Change timestamp |

**Foreign Keys:**
- `vehicle_id` references `vehicles.id` (cascadeOnDelete)
- `changed_by_user_id` references `users.id` (cascadeOnDelete)

**Note:** No timestamps. Uses `changed_at` field. Table name is `price_history`.

**Relationships:**
- `belongsTo` Vehicle, User (changed by)

#### `audit_logs`
System-wide audit logging.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| actor_id | BIGINT | Actor user/system ID |
| audit_actor_type_id | INT (FK) | Foreign key to `audit_actor_types.id` |
| action | VARCHAR(100) | Action performed |
| target_type | VARCHAR(50) | Target model type |
| target_id | BIGINT | Target record ID |
| metadata | JSON (NULL) | Additional audit data (cast to array) |
| ip_address | VARCHAR(45) (NULL) | Actor IP address |
| created_at | DATETIME | Creation timestamp |

**Foreign Keys:**
- `audit_actor_type_id` references `audit_actor_types.id` (cascadeOnDelete)

**Model Features:**
- **Casts**: `metadata` is cast to array
- **No Timestamps**: Only has `created_at`, no `updated_at`
- **Table Name**: `audit_logs`

**Relationships:**
- `belongsTo` AuditActorType

#### `audit_actor_types`
Audit actor type lookup.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Actor type name |

**Constants (Model):**
- `ADMIN = 1`
- `DEALER = 2`
- `SYSTEM = 3`

#### `api_logs`
API performance and status logging.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| api_service | VARCHAR(50) | Service name |
| endpoint | VARCHAR(255) | API endpoint |
| status_code | INT | HTTP status code |
| execution_time_ms | INT | Execution time in milliseconds |
| created_at | DATETIME | Creation timestamp |

**Note:** No timestamps. Only has `created_at`. Table name is `api_logs`.

---

## Key Relationships Diagram

```
Users
  ├── belongsTo UserStatus
  ├── hasMany Dealer (owned dealers via `user_id`)
  ├── hasMany DealerStaff (staff memberships)
  ├── hasMany Vehicle, Favorite, SavedSearch, Lead (buyer/assigned), Enquiry, ChatMessage, PriceHistory (changed_by), ListingViewsLog, UserPlanOverride
  └── Note: Soft deletes enabled, JWT Subject, Spatie Permission roles

Dealers
  ├── belongsTo User (owner via `user_id`)
  ├── hasMany DealerStaff (staff members)
  ├── hasMany Vehicle, Lead, DealerSubscription, DealerPlanOverride
  └── Note: Soft deletes enabled

DealerStaff
  ├── belongsTo Dealer, User, Role (Spatie Permission)
  └── Note: Links users to dealers with roles

Vehicles
  ├── belongsTo Dealer (nullable), User, Brand, VehicleModel (model), ModelYear, ListingType, GearType
  ├── hasOne VehicleDetail, FeaturedListing
  ├── hasMany VehicleImage, Favorite, Lead, Enquiry, PriceHistory, ListingViewsLog
  ├── belongsToMany Equipment (via vehicle_equipment pivot)
  └── Note: Soft deletes enabled, caching for lookup data, auto-generated title accessor

VehicleDetails
  ├── belongsTo Vehicle, PriceType, Condition, SalesType, Variant, Euronom
  └── Note: version, gear_type_id, fuel_efficiency moved to vehicles table for optimization

Brands
  └── hasMany VehicleModel (models), Vehicle

VehicleModel (models)
  ├── belongsTo Brand
  └── hasMany Vehicle

Equipment
  ├── belongsTo EquipmentType
  └── belongsToMany Vehicle (via vehicle_equipment pivot)

EquipmentTypes
  └── hasMany Equipment

BodyTypes, Colors, Permits, Types, Uses, PriceTypes, Conditions, SalesTypes
  └── Lookup tables (used for caching in VehicleDetail model)

Variants, Euronorms
  └── Lookup tables (used in VehicleDetail model)

FeaturedListings
  └── belongsTo Vehicle (one-to-one relationship)

ListingTypes, FuelTypes, GearTypes, VehicleListStatuses, ModelYears, Categories
  └── Lookup tables (used for caching in Vehicle model)

Leads
  ├── belongsTo Vehicle, User (buyer), Dealer, User (assigned), LeadStage, LeadIntent, Source, LeadCategory
  ├── hasMany LeadStageHistory, ChatThread
  └── Note: No timestamps (only created_at)

LeadStages
  └── hasMany Lead

LeadIntents
  └── hasMany Lead

LeadCategories
  └── hasMany Lead

Sources
  └── hasMany Lead

Enquiries
  ├── belongsTo User, Vehicle, Contact (nullable, conditional)
  └── Note: Uses HasSerialNumber trait

ChatThreads
  ├── belongsTo Lead
  └── hasMany ChatMessage

ChatMessages
  ├── belongsTo ChatThread, User (sender)
  └── Note: No timestamps (only created_at)

Plans
  ├── hasMany PlanPriceHistory, PlanFeature, DealerSubscription, PlanAvailability
  ├── belongsToMany Feature (through PlanFeature pivot, with value)
  └── Note: Soft deletes enabled

Features
  ├── belongsTo FeatureValueType
  ├── belongsToMany Plan (through PlanFeature pivot, with value)
  └── hasMany PlanFeature, UserPlanOverride, DealerPlanOverride

PlanFeatures (Pivot)
  ├── belongsTo Plan, Feature
  └── Note: Stores feature values for plans

DealerSubscriptions
  ├── belongsTo Dealer, Plan, SubscriptionStatus
  └── Note: Immutable pattern, soft deletes enabled, no timestamps (only created_at)

UserPlanOverrides, DealerPlanOverrides
  ├── belongsTo User/Dealer, Feature
  └── Note: No timestamps (only created_at)

Pages
  └── belongsTo PageStatus

Blogs
  └── Note: Standalone CMS content

PriceHistory, ListingViewsLog, AuditLogs, ApiLogs
  └── Analytics and logging tables
```

## Design Decisions

### 1. Immutable Subscription Pattern
The `dealer_subscriptions` table follows an immutable pattern where upgrades/downgrades create new rows rather than updating existing ones. This provides a complete audit trail of subscription changes.

### 2. Composite Indexes for Vehicle Search
Multiple composite indexes on `vehicles` table optimize common search queries:
- `(vehicle_list_status_id, published_at)` - Active listings
- `(vehicle_list_status_id, price)` - Price sorting
- Note: `location_id` was removed from vehicles table (migration 2026_01_15_130000)

### 3. JSON Fields for Flexibility
Several tables use JSON/array fields for flexible data storage:
- `saved_searches.filters` - Search criteria (JSON)
- `audit_logs.metadata` - Additional audit data (JSON)
- `vehicle_details.owners` - Vehicle owners information (cast to array)

### 4. Status Lookup Tables with Constants
All status/enum values use lookup tables with constants defined in the model classes. This provides type safety and easy reference in code.

### 5. Dealer-User Relationship
The `dealer_staff` table links staff members to dealers with auto-generated usernames. Each dealer has one owner (via `dealers.user_id`) and can have multiple staff members (via `dealer_staff` table).

### 6. Lead Management System
Leads track the full lifecycle from inquiry to sale, with:
- Stage management through `lead_stages`
- Stage change history in `lead_stage_history`
- Communication through `chat_threads` and `chat_messages`

### 7. Subscription Feature System
The subscription system uses a flexible feature-based approach:
- Features defined in `features` table
- Plan features mapped in `plan_features`
- Overrides at user/dealer level via `user_plan_overrides` and `dealer_plan_overrides`

## Laravel Package Tables

The following tables are managed by Laravel packages and are preserved:

### Spatie Permission Package
- `permissions` - System permissions
- `roles` - User roles
- `model_has_permissions` - Permission assignments
- `model_has_roles` - Role assignments
- `role_has_permissions` - Role-permission mapping

### Laravel Core Tables
- `cache` - Cache storage
- `cache_locks` - Cache locks
- `jobs` - Queue jobs
- `job_batches` - Job batches
- `sessions` - User sessions
- `personal_access_tokens` - API tokens

## Migration Order

Migrations are ordered by dependency (timestamps ensure correct execution order):

1. Lookup tables (user_statuses, fuel_types, transmissions, vehicle_list_statuses, lead_stages, sources, feature_value_types, page_statuses, subscription_statuses, audit_actor_types) - 054109-054117
2. Core business tables (dealers, dealer_staff, locations) - 054220-054222
3. Vehicle lookup tables (categories, brands, model_years) - 060624-060626
4. Vehicle tables (vehicles, vehicle_images, vehicle_details) - 060648-060707
5. Vehicle detail lookup tables (body_types, colors, equipments, types, permits, uses) - 081020-081024
6. Additional vehicle lookup tables (price_types, conditions, gear_types, sales_types, models, listing_types) - 100000-100007
7. Vehicle equipment pivot table - 091947
8. User feature tables (favorites, saved_searches) - 060650, 054328
9. Lead management tables (leads, lead_stage_history, chat_threads, chat_messages) - 060651, 060654-060656
10. CMS tables (pages, blogs) - 054426-054427
11. Subscription tables:
    - 054511: features, plans (both independent, run alphabetically)
    - 054512: plan_features (depends on features, plans)
    - 054513: user_plan_overrides (depends on features, users)
    - 054514: dealer_plan_overrides (depends on features, dealers)
    - 054515: plan_price_history (depends on plans)
    - 054516: plan_availability (depends on plans, roles)
    - 054517: dealer_subscriptions (depends on plans, dealers, subscription_statuses)
12. Analytics tables (price_history, listing_views_log, audit_logs, api_logs) - 060652-060653, 054600
13. Additional tables:
    - Equipment types - 031211
    - Variants, Euronorms - 120000-120001
    - Featured listings - 080820
14. Schema modifications:
    - Users: Added address, postcode - 120004
    - Vehicles: Made title nullable, dealer_id nullable, removed location_id, removed mileage, added model_id, listing_type_id, range_km, charging_type, moved version/gear_type_id/fuel_efficiency from vehicle_details - Various migrations
    - Vehicle details: Added variant_id, euronom_id, servicebog, seller contact fields, annual_tax, owners; Changed coupling to boolean, wheels to text; Removed model_year - Various migrations

## Notes

- All timestamps use Laravel's `created_at`/`updated_at` conventions where applicable
- Foreign keys use `cascadeOnDelete()` or `nullOnDelete()` as appropriate
- Integer foreign keys reference lookup tables with INT primary keys
- BIGINT foreign keys reference main tables with BIGINT primary keys
- All status constants are defined as class constants in their respective models

