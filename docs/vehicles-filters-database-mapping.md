# Vehicles Filters -> Database Mapping (Manual Comparison)

This checklist maps each filter shown in `backend/resources/views/vehicles.blade.php` to where its data lives in the database.

It also notes how the public listing backend (`HomeController` -> `VehicleService::getPublicVehicles()` via `getPublicVehiclesWithAdvancedFilters()`) applies filters, so manual DB comparisons match reality.

---

## 1) Filter keys: where they come from

1. The allowed filter keys are defined in `backend/app/Http/Controllers/HomeController.php` as `VEHICLE_FILTER_KEYS`.
2. The sidebar inputs in `backend/resources/views/vehicles.blade.php` use these keys as HTML `name="..."` attributes.

---

## 2) Important backend behavior (current state)

For the public `/vehicles` page:

1. `HomeController::showVehicles()` builds `currentFilters` from request query params.
2. It normalizes `euronorm` (name) to `euronom_id` when needed, mirrors `km_driven_*` into `mileage_*` for the mileage merge, and calls `VehicleService::getPublicVehiclesWithAdvancedFilters([], $input, ...)`, which merges mileage aliases and delegates to `getPublicVehicles()`.
3. `VehicleService::getPublicVehicles()` applies filters in `applyPublicListingFilters()` using **`vehicles`**, **`vehicle_equipment`** / **`equipments`**, and **DMR** tables (`dmr_fact_vehicles`, bridges, etc.). It does **not** use `vehicle_details`.

---

## 3) Filters the backend applies (exact DB mapping)

### Direct columns on `vehicles`

- `search` — `title`, `registration`, `description` (`LIKE`).
- `km_driven_from` / `km_driven_to` — `vehicles.km_driven` (includes NULL rows per range logic).
- `price_from` / `price_to` — `vehicles.price` (includes NULL rows per range logic).
- `condition_id`, `gear_type_id`, `charging_type` — respective `vehicles.*` columns.
- `battery_capacity_from`, `battery_capacity_to` — `vehicles.battery_capacity`.
- `range_km_from`, `range_km_to` — `vehicles.range_km`.
- `ownership_tax_from`, `ownership_tax_to` — `vehicles.calculated_ownership_tax`.
- `listing_type_id`, `sales_type_id`, `price_type_id`, `category_id`, `type_id`, `transmission_id` — respective `vehicles.*` FK columns (where populated).
- `towing_weight`, `wheels`, `airbags` — `vehicles.towing_weight`, `wheels`, `airbags` (filters match the plan semantics already coded in `VehicleService`).
- `drive_axles` — JSON array on `vehicles.drive_axles`; filter uses `orWhereJsonContains` per selected token.
- `is_import`, `is_factory_new` — booleans on `vehicles`.
- `fuel_efficiency_from`, `fuel_efficiency_to` — `vehicles.fuel_efficiency` **or** DMR `drivmiddelLines.motor_km_per_liter` (OR group).

### `vehicle_equipment` (equipment)

- `equipment_ids` / `equipment_id` — `whereHas('equipment', ...)` on `vehicle_equipment.equipment_id`.

### DMR fact / bridges (`whereHas` / joins)

- `year_from` / `year_to` — `dmr_fact_vehicles.model_aar`.
- `first_registration_year_from` / `first_registration_year_to` — `dmr_fact_vehicles.foerste_registrering_dato` (`whereYear`).
- `body_type_id` — `dmr_fact_vehicles.body_type_id` after `resolveDmrBodyTypeIds()` (legacy `body_types.id` → `dmr_body_types.id` by name).
- `brand_id`, `model_id`, `model_year_id`, `fuel_type_id` — existing resolution (`resolveDmrBrandIds`, `resolveDmrModelIds`, model year names vs `model_aar`, `resolveDmrDriveEnergyIds` + `dmr_bridge_vehicle_drivmiddel`).
- `engine_power_from` / `engine_power_to` — UI uses **HP**; query converts to **kW** (÷ 1.36) vs `dmr_fact_vehicles.motor_stoerste_effekt`.
- `top_speed_from` / `top_speed_to` — `dmr_fact_vehicles.maksimum_hastighed`.
- `weight_from` / `weight_to` — `dmr_fact_vehicles.teknisk_total_vaegt`.
- `doors`, `seats_min`, `seats_max`, `axles` — `antal_doere`, `siddepladser_minimum`, `siddepladser_maksimum`, `aksel_antal`.
- `engine_displacement_from` / `engine_displacement_to` — `dmr_fact_vehicles.motor_slag_volumen` (liters; values > 50 treated as cc and divided by 1000).
- `ncap_five` — `dmr_fact_vehicles.ncap_test`.
- `variant_id` — `dmr_fact_vehicles.variant_id`.
- `color_id` — `dmr_fact_vehicles.colour_id` after `resolveDmrColourIds()` (legacy `colors.id` → `dmr_colours.id` by name).
- `euronom_id` — `dmr_fact_vehicles.emission_norm_id` after `resolveDmrEmissionNormIds()` (`euronorms.id` → `dmr_emission_norms.id` by name).
- `use_id` — `dmr_fact_vehicles.vehicle_use_id` after `resolveDmrVehicleUseIds()` (`uses.id` → `dmr_vehicle_uses.id` by name).

### Intentionally not filtered in DMR

- `engine_cylinders` — reserved; no DMR column wired yet.

---

## 4) Full mapping checklist (sidebar keys)

Legend:

- **Current data** — primary tables used by the public listing query.
- **Backend applied?** — whether `applyPublicListingFilters()` uses the key.

| Filter key | Current data (DMR / vehicles) | Backend applied? |
|---|---|---|
| `search` | `vehicles.title`, `registration`, `description` | Yes |
| `brand_id` | `dmr_models.brand_id` (after resolution) | Yes |
| `model_id` | `dmr_variants.model_id` (after resolution) | Yes |
| `model_year_id` | `dmr_fact_vehicles.model_aar` vs `model_years.name` | Yes |
| `fuel_type_id` | `dmr_bridge_vehicle_drivmiddel.drive_energy_id` (after resolution) | Yes |
| `year_from` / `year_to` | `dmr_fact_vehicles.model_aar` | Yes |
| `first_registration_year_from` / `first_registration_year_to` | `dmr_fact_vehicles.foerste_registrering_dato` | Yes |
| `body_type_id` | `dmr_fact_vehicles.body_type_id` (after `resolveDmrBodyTypeIds`) | Yes |
| `gear_type_id` | `vehicles.gear_type_id` | Yes |
| `condition_id` | `vehicles.condition_id` | Yes |
| `equipment_ids` / `equipment_id` | `vehicle_equipment.equipment_id` | Yes |
| `km_driven_from` / `km_driven_to` | `vehicles.km_driven` | Yes |
| `price_from` / `price_to` | `vehicles.price` | Yes |
| `battery_capacity_from` / `battery_capacity_to` | `vehicles.battery_capacity` | Yes |
| `range_km_from` / `range_km_to` | `vehicles.range_km` | Yes |
| `charging_type` | `vehicles.charging_type` | Yes |
| `ownership_tax_from` / `ownership_tax_to` | `vehicles.calculated_ownership_tax` | Yes |
| `engine_power_from` / `engine_power_to` | `dmr_fact_vehicles.motor_stoerste_effekt` (kW; HP from UI) | Yes |
| `top_speed_from` / `top_speed_to` | `dmr_fact_vehicles.maksimum_hastighed` | Yes |
| `weight_from` / `weight_to` | `dmr_fact_vehicles.teknisk_total_vaegt` | Yes |
| `doors` | `dmr_fact_vehicles.antal_doere` | Yes |
| `seats_min` / `seats_max` | `dmr_fact_vehicles.siddepladser_*` | Yes |
| `axles` | `dmr_fact_vehicles.aksel_antal` | Yes |
| `wheels` | `vehicles.wheels` | Yes |
| `drive_axles` | `vehicles.drive_axles` (JSON) | Yes |
| `airbags` | `vehicles.airbags` | Yes |
| `ncap_five` | `dmr_fact_vehicles.ncap_test` | Yes |
| `color_id` | `dmr_fact_vehicles.colour_id` (after `resolveDmrColourIds`) | Yes |
| `use_id` | `dmr_fact_vehicles.vehicle_use_id` (after `resolveDmrVehicleUseIds`) | Yes |
| `variant_id` | `dmr_fact_vehicles.variant_id` | Yes |
| `type_id` | `vehicles.type_id` | Yes |
| `transmission_id` | `vehicles.transmission_id` | Yes |
| `euronom_id` / `euronorm` | `dmr_fact_vehicles.emission_norm_id` (after `resolveDmrEmissionNormIds`; name param normalized in controller) | Yes |
| `towing_weight` | `vehicles.towing_weight` | Yes |
| `fuel_efficiency_from` / `fuel_efficiency_to` | `vehicles.fuel_efficiency` OR DMR `motor_km_per_liter` | Yes |
| `is_import` / `is_factory_new` | `vehicles.is_import`, `is_factory_new` | Yes |
| `listing_type_id` | `vehicles.listing_type_id` | Yes |
| `sales_type_id` | `vehicles.sales_type_id` | Yes |
| `price_type_id` | `vehicles.price_type_id` | Yes |
| `category_id` | `vehicles.category_id` | Yes |
| `engine_displacement_from` / `engine_displacement_to` | `dmr_fact_vehicles.motor_slag_volumen` | Yes |
| `engine_cylinders` | — | No (not implemented) |
| `sort` | ordering only (`applySorting`) | N/A |

---

## 5) Sort keys (`sort` query param)

Handled in `VehicleService::applySorting()` (DMR joins use `select('vehicles.*')` when no prior joins, same pattern as other sort cases):

| Sort key | Notes |
|---|---|
| `price_asc` / `price_desc` | `vehicles.price` |
| `date_asc` / `date_desc` | `vehicles.created_at` |
| `year_asc` / `year_desc` | `dmr_fact_vehicles.model_aar` |
| `mileage_asc` / `mileage_desc` | `vehicles.km_driven` (COALESCE) |
| `range_asc` / `range_desc` | `vehicles.range_km` |
| `battery_asc` / `battery_desc` | `vehicles.battery_capacity` |
| `brand_asc` / `brand_desc` | DMR brand name via joins |
| `first_reg_asc` / `first_reg_desc` | `dmr_fact_vehicles.foerste_registrering_dato` |
| `engine_power_asc` / `engine_power_desc` | `dmr_fact_vehicles.motor_stoerste_effekt` |
| `top_speed_asc` / `top_speed_desc` | `dmr_fact_vehicles.maksimum_hastighed` |
| `towing_weight_asc` / `towing_weight_desc` | `vehicles.towing_weight` |
| `ownership_tax_asc` / `ownership_tax_desc` | `vehicles.calculated_ownership_tax` |
| `fuel_efficiency_asc` / `fuel_efficiency_desc` | `vehicles.fuel_efficiency` |
| `best_match` | Same as default listing order (`vehicles.id` desc) |
| `standard` / default | `vehicles.id` desc |
| `distance_asc` / `distance_desc` | Placeholder; orders by `id` desc |

---

## 6) Quick “how to compare” workflow (recommended)

1. Pick one filter key you want to validate.
2. Check which bucket it belongs to: `vehicles` columns, `dmr_fact_vehicles`, DMR bridges (`dmr_bridge_vehicle_drivmiddel`), or `vehicle_equipment`.
3. For `brand_id` / `model_id` / `fuel_type_id` / `model_year_id` / `body_type_id` / `color_id` / `euronom_id` / `use_id`, apply the resolver rules from Section 3 before comparing IDs in the DB.
4. If the filter is marked “Backend applied? = No”, don’t expect the `/vehicles` result set to change for that key until implemented in `applyPublicListingFilters()`.
