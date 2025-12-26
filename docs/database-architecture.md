<!--
Schema Checksum: 67b945a0b11342e7b722c0b49183ee34938695ea303a338cbfe8f55b7a9ea89f
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
| phone | VARCHAR(30) | Phone number |
| password | VARCHAR(255) | Hashed password |
| status_id | INT (FK) | Foreign key to `user_statuses.id` |
| email_verified_at | DATETIME | Email verification timestamp |
| remember_token | VARCHAR(100) | Remember me token |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `email` (unique)
- `status_id`

**Relationships:**
- `belongsTo` UserStatus
- `hasMany` DealerUser, Vehicle, Favorite, SavedSearch, Lead (buyer/assigned), ChatMessage, PriceHistory, ListingViewsLog, UserPlanOverride

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
| cvr | VARCHAR(20) | Danish CVR number (unique) |
| address | TEXT | Street address |
| city | VARCHAR(100) | City name |
| postcode | VARCHAR(10) | Postal code |
| country_code | CHAR(2) | Country code (default: 'DK') |
| logo_path | VARCHAR(255) | Logo file path |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `cvr` (unique)
- `postcode`

**Accessors (Model):**
- `logo_url` - Full URL to logo image

**Relationships:**
- `hasMany` DealerUser, Vehicle, Lead, DealerSubscription, DealerPlanOverride

#### `dealer_users`
Pivot table linking users to dealers with roles.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| role_id | BIGINT (FK) | Foreign key to `roles.id` (Spatie Permission) |
| created_at | DATETIME | Creation timestamp |

**Constraints:**
- Unique constraint on `(dealer_id, user_id)`

**Constants (Model):**
- `ROLE_OWNER = 1`
- `ROLE_MANAGER = 2`
- `ROLE_STAFF = 3`

**Relationships:**
- `belongsTo` Dealer, User, Role (Spatie Permission)

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
| title | VARCHAR(255) | Listing title |
| registration | VARCHAR(20) | License plate number |
| vin | VARCHAR(17) | Vehicle Identification Number |
| dealer_id | BIGINT (FK) | Foreign key to `dealers.id` |
| user_id | BIGINT (FK) | Foreign key to `users.id` (creator) |
| category_id | INT (FK, NULL) | Foreign key to `categories.id` |
| location_id | BIGINT (FK) | Foreign key to `locations.id` |
| brand_id | INT (FK, NULL) | Foreign key to `brands.id` |
| model_id | INT (FK, NULL) | Foreign key to `models.id` |
| model_year_id | INT (FK, NULL) | Foreign key to `model_years.id` |
| km_driven | INT (NULL) | Kilometers driven |
| fuel_type_id | INT (FK) | Foreign key to `fuel_types.id` |
| price | INT | Price in DKK |
| mileage | INT (NULL) | Odometer reading |
| battery_capacity | INT (NULL) | Battery capacity (for electric vehicles) |
| engine_power | INT (NULL) | Engine power |
| towing_weight | INT (NULL) | Towing weight capacity |
| ownership_tax | INT (NULL) | Ownership tax amount |
| first_registration_date | DATE (NULL) | First registration date |
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
- `(vehicle_list_status_id, mileage)` - For mileage filtering
- `(location_id, price)` - For location-based search
- `category_id` - For category filtering
- `brand_id` - For brand filtering
- `model_id` - For model filtering
- `model_year_id` - For model year filtering
- `listing_type_id` - For listing type filtering

**Relationships:**
- `belongsTo` Dealer, User, Location, Brand, VehicleModel (model), ModelYear, ListingType
- `hasOne` VehicleDetail
- `hasMany` VehicleImage, Favorite, Lead, PriceHistory, ListingViewsLog
- `belongsToMany` Equipment (via vehicle_equipment)

**Model Features:**
- **Caching**: Lookup data (categories, brands, models, model_years, fuel_types, vehicle_list_statuses, listing_types) is cached using static property + Laravel Cache facade (24-hour TTL)
- **Accessors**: Automatically appends resolved names (`category_name`, `brand_name`, `model_name`, `model_year_name`, `fuel_type_name`, `vehicle_list_status_name`, `listing_type_name`) to API responses
- **Default Ordering**: Global scope applies `ORDER BY id DESC` by default (can be overridden with explicit `orderBy`)
- **Soft Deletes**: Enabled for data retention

#### `vehicle_details`
Extended vehicle information and specifications.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| vehicle_id | BIGINT (FK, UNIQUE) | Foreign key to `vehicles.id` |
| description | TEXT (NULL) | Full vehicle description |
| views_count | INT | View counter (default: 0) |
| vin_location | VARCHAR(255) (NULL) | VIN location |
| type_id | INT (FK, NULL) | Foreign key to `types.id` |
| version | VARCHAR(100) (NULL) | Vehicle version |
| type_name | VARCHAR(255) (NULL) | Type name |
| registration_status | VARCHAR(100) (NULL) | Registration status |
| registration_status_updated_date | DATE (NULL) | Registration status update date |
| expire_date | DATE (NULL) | Registration expiration date |
| status_updated_date | DATE (NULL) | Status update date |
| total_weight | INT (NULL) | Total weight |
| vehicle_weight | INT (NULL) | Vehicle weight |
| technical_total_weight | INT (NULL) | Technical total weight |
| coupling | INT (NULL) | Coupling weight |
| towing_weight_brakes | INT (NULL) | Towing weight with brakes |
| minimum_weight | INT (NULL) | Minimum weight |
| gross_combination_weight | INT (NULL) | Gross combination weight |
| fuel_efficiency | DECIMAL(8,2) (NULL) | Fuel efficiency |
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
| wheels | INT (NULL) | Number of wheels |
| extra_equipment | TEXT (NULL) | Extra equipment details |
| axles | INT (NULL) | Number of axles |
| drive_axles | INT (NULL) | Number of drive axles |
| wheelbase | INT (NULL) | Wheelbase measurement |
| leasing_period_start | DATE (NULL) | Leasing period start |
| leasing_period_end | DATE (NULL) | Leasing period end |
| use_id | INT (FK, NULL) | Foreign key to `uses.id` |
| color_id | INT (FK, NULL) | Foreign key to `colors.id` |
| body_type_id | INT (FK, NULL) | Foreign key to `body_types.id` |
| dispensations | TEXT (NULL) | Dispensations |
| permits | TEXT (NULL) | Permits |
| ncap_five | BOOLEAN (NULL) | NCAP 5-star rating |
| airbags | INT (NULL) | Number of airbags |
| integrated_child_seats | INT (NULL) | Number of integrated child seats |
| seat_belt_alarms | INT (NULL) | Number of seat belt alarms |
| euronorm | VARCHAR(50) (NULL) | Euro norm standard |
| price_type_id | INT (FK, NULL) | Foreign key to `price_types.id` |
| condition_id | INT (FK, NULL) | Foreign key to `conditions.id` |
| gear_type_id | INT (FK, NULL) | Foreign key to `gear_types.id` |
| sales_type_id | INT (FK, NULL) | Foreign key to `sales_types.id` |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

**Indexes:**
- `vehicle_id` (unique)
- `type_id`
- `use_id`
- `color_id`
- `body_type_id`
- `price_type_id`
- `condition_id`
- `gear_type_id`
- `sales_type_id`

**Foreign Keys:**
- `type_id` references `types.id` (nullOnDelete)
- `use_id` references `uses.id` (nullOnDelete)
- `color_id` references `colors.id` (nullOnDelete)
- `body_type_id` references `body_types.id` (nullOnDelete)
- `price_type_id` references `price_types.id` (nullOnDelete)
- `condition_id` references `conditions.id` (nullOnDelete)
- `gear_type_id` references `gear_types.id` (nullOnDelete)
- `sales_type_id` references `sales_types.id` (nullOnDelete)

**Model Features:**
- **Caching**: Lookup data (types, uses, colors, body_types, price_types, conditions, gear_types, sales_types) is cached using static property + Laravel Cache facade (24-hour TTL)
- **Accessors**: Automatically appends resolved names (`type_name_resolved`, `use_name`, `color_name`, `body_type_name`, `price_type_name`, `condition_name`, `gear_type_name`, `sales_type_name`) to API responses
- No eager-loading of constant relations required

**Relationships:**
- `belongsTo` Vehicle, PriceType, Condition, GearType, SalesType

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
- `hasMany` VehicleModel (models)

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

**Note:** No timestamps. Reference data for equipment options.

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
| filters | JSON | Search filter criteria |
| created_at | DATETIME | Creation timestamp |

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
| source_id | INT (FK) | Foreign key to `sources.id` |
| last_activity_at | DATETIME | Last activity timestamp |
| created_at | DATETIME | Creation timestamp |

**Indexes:**
- `dealer_id`
- `lead_stage_id`

**Relationships:**
- `belongsTo` Vehicle, User (buyer), Dealer, User (assigned), LeadStage, Source
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
| from_stage_id | INT | Previous stage ID |
| to_stage_id | INT | New stage ID |
| changed_by_user_id | BIGINT (FK) | Foreign key to `users.id` |
| changed_at | DATETIME | Change timestamp |

**Relationships:**
- `belongsTo` Lead, User (changed by)

#### `sources`
Lead source lookup (website, phone, referral, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Primary key |
| name | VARCHAR(50) | Source name |

#### `chat_threads`
Chat conversation threads linked to leads.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| lead_id | BIGINT (FK) | Foreign key to `leads.id` |
| created_at | DATETIME | Creation timestamp |

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
| is_internal | BOOLEAN | Internal note flag |
| created_at | DATETIME | Creation timestamp |

**Indexes:**
- `thread_id`

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
| content | LONGTEXT | Page content |
| meta_title | VARCHAR(255) | SEO meta title |
| meta_description | TEXT | SEO meta description |
| page_status_id | INT (FK) | Foreign key to `page_statuses.id` |

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
| meta_title | VARCHAR(255) | SEO meta title |
| meta_description | TEXT | SEO meta description |
| published_at | DATETIME | Publication timestamp |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

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
| ends_at | DATETIME | Effective end date (nullable) |

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
| ends_at | DATETIME | Subscription end date (nullable) |
| auto_renew | BOOLEAN | Auto-renewal flag |
| created_at | DATETIME | Creation timestamp |

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
| is_enabled | BOOLEAN | Availability flag |
| created_at | DATETIME | Creation timestamp |

**Relationships:**
- `belongsTo` Plan, Role (Spatie Permission)

#### `user_plan_overrides`
User-level feature overrides.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT (PK) | Primary key |
| user_id | BIGINT (FK) | Foreign key to `users.id` |
| feature_id | BIGINT (FK) | Foreign key to `features.id` |
| override_value | VARCHAR(100) | Override value |
| expires_at | DATETIME | Expiration date (nullable) |
| created_at | DATETIME | Creation timestamp |

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
| expires_at | DATETIME | Expiration date (nullable) |
| created_at | DATETIME | Creation timestamp |

**Relationships:**
- `belongsTo` Dealer, Feature

---

### Analytics & Logging

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

**Relationships:**
- `belongsTo` Vehicle, User (changed by)

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

**Relationships:**
- `belongsTo` Vehicle, User (if logged in)

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
| metadata | JSON | Additional audit data |
| ip_address | VARCHAR(45) | Actor IP address |
| created_at | DATETIME | Creation timestamp |

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

---

## Key Relationships Diagram

```
Users
  ├── belongsTo UserStatus
  ├── belongsToMany Dealers (through DealerUser)
  ├── hasMany Vehicles
  ├── hasMany Favorites
  ├── hasMany SavedSearches
  ├── hasMany Leads (as buyer/assigned)
  └── hasMany ChatMessages

Dealers
  ├── hasMany DealerUsers
  ├── hasMany Vehicles
  ├── hasMany Leads
  ├── hasMany DealerSubscriptions
  └── hasMany DealerPlanOverrides

Vehicles
  ├── belongsTo Dealer, User, Location, Brand, VehicleModel (model), ModelYear, ListingType
  ├── hasOne VehicleDetail
  ├── hasMany VehicleImages
  ├── hasMany Favorites
  ├── hasMany Leads
  ├── hasMany PriceHistory
  ├── hasMany ListingViewsLog
  └── belongsToMany Equipment (via vehicle_equipment)

VehicleDetails
  ├── belongsTo Vehicle
  ├── belongsTo PriceType, Condition, GearType, SalesType

Brands
  └── hasMany VehicleModel (models)

VehicleModel (models)
  ├── belongsTo Brand
  └── hasMany Vehicle

Equipment
  └── belongsToMany Vehicle (via vehicle_equipment)

BodyTypes, Colors, Permits, Types, Uses, PriceTypes, Conditions, GearTypes, SalesTypes
  └── Lookup tables (used for caching in VehicleDetail model)

ListingTypes
  └── Lookup table (used for caching in Vehicle model)

Leads
  ├── belongsTo Vehicle, User (buyer), Dealer, User (assigned), LeadStage, Source
  ├── hasMany LeadStageHistory
  └── hasMany ChatThreads

Plans
  ├── hasMany PlanPriceHistory
  ├── hasMany PlanFeatures
  ├── hasMany DealerSubscriptions
  └── belongsToMany Features (through PlanFeatures)
```

## Design Decisions

### 1. Immutable Subscription Pattern
The `dealer_subscriptions` table follows an immutable pattern where upgrades/downgrades create new rows rather than updating existing ones. This provides a complete audit trail of subscription changes.

### 2. Composite Indexes for Vehicle Search
Multiple composite indexes on `vehicles` table optimize common search queries:
- `(vehicle_list_status_id, published_at)` - Active listings
- `(vehicle_list_status_id, price)` - Price sorting
- `(location_id, price)` - Location-based search

### 3. JSON Fields for Flexibility
Several tables use JSON fields for flexible data storage:
- `vehicles.specs` - Vehicle specifications
- `vehicles.equipment` - Equipment list
- `saved_searches.filters` - Search criteria
- `audit_logs.metadata` - Additional audit data

### 4. Status Lookup Tables with Constants
All status/enum values use lookup tables with constants defined in the model classes. This provides type safety and easy reference in code.

### 5. Dealer-User Relationship
The `dealer_users` pivot table links users to dealers with roles. This allows users to be associated with multiple dealers in different roles.

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

1. Lookup tables (user_statuses, fuel_types, etc.) - 054109-054117
2. Core business tables (dealers, locations) - 054220-054222
3. Vehicle tables - 054247-054248
4. User feature tables - 054327-054328
5. Lead management tables - 054351-054354
6. CMS tables - 054426-054427
7. Subscription tables:
   - 054511: features, plans (both independent, run alphabetically)
   - 054512: plan_features (depends on features, plans)
   - 054513: user_plan_overrides (depends on features, users)
   - 054514: dealer_plan_overrides (depends on features, dealers)
   - 054515: plan_price_history (depends on plans)
   - 054516: plan_availability (depends on plans, roles)
   - 054517: dealer_subscriptions (depends on plans, dealers, subscription_statuses)
8. Analytics tables - 054600

## Notes

- All timestamps use Laravel's `created_at`/`updated_at` conventions where applicable
- Foreign keys use `cascadeOnDelete()` or `nullOnDelete()` as appropriate
- Integer foreign keys reference lookup tables with INT primary keys
- BIGINT foreign keys reference main tables with BIGINT primary keys
- All status constants are defined as class constants in their respective models

