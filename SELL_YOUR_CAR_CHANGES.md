# Sell Your Car Form - Implementation Commands

## Database Migrations

### Step 1: Create Variants Table
```bash
php artisan make:migration create_variants_table
```
Then edit the migration file and add:
```php
Schema::create('variants', function (Blueprint $table) {
    $table->integerIncrements('id');
    $table->string('name', 100)->unique();
});
```

### Step 2: Create Euronorms Table
```bash
php artisan make:migration create_euronorms_table
```
Then edit the migration file and add:
```php
Schema::create('euronorms', function (Blueprint $table) {
    $table->integerIncrements('id');
    $table->string('name', 50)->unique();
});
```

### Step 3: Add Variant, Euronom, Servicebog to Vehicle Details
```bash
php artisan make:migration add_variant_id_euronom_id_servicebog_to_vehicle_details
```
Then edit the migration file and add:
```php
Schema::table('vehicle_details', function (Blueprint $table) {
    if (!Schema::hasColumn('vehicle_details', 'variant_id')) {
        $table->foreignId('variant_id')->nullable()->after('vehicle_external_id')->constrained('variants')->nullOnDelete();
    }
    if (!Schema::hasColumn('vehicle_details', 'euronom_id')) {
        $table->foreignId('euronom_id')->nullable()->after('seat_belt_alarms')->constrained('euronorms')->nullOnDelete();
    }
    if (!Schema::hasColumn('vehicle_details', 'servicebog')) {
        $table->enum('servicebog', ['yes', 'no', 'default'])->default('default')->after('euronom_id');
    }
});
```

### Step 4: Replace Euronorm String with Euronom ID
```bash
php artisan make:migration replace_euronorm_with_euronom_id_in_vehicle_details
```
Then edit the migration file and add:
```php
Schema::table('vehicle_details', function (Blueprint $table) {
    if (Schema::hasColumn('vehicle_details', 'euronorm')) {
        $table->dropColumn('euronorm');
    }
    if (!Schema::hasColumn('vehicle_details', 'euronom_id')) {
        $table->foreignId('euronom_id')->nullable()->after('seat_belt_alarms')->constrained('euronorms')->nullOnDelete();
    }
});
```

### Step 5: Add Address and Postcode to Users
```bash
php artisan make:migration add_address_postcode_to_users_table
```
Then edit the migration file and add:
```php
Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'address')) {
        $table->string('address', 255)->nullable()->after('phone');
    }
    if (!Schema::hasColumn('users', 'postcode')) {
        $table->string('postcode', 10)->nullable()->after('address');
    }
});
```

### Step 6: Remove Mileage from Vehicles
```bash
php artisan make:migration remove_mileage_from_vehicles_table
```
Then edit the migration file and add:
```php
Schema::table('vehicles', function (Blueprint $table) {
    if (Schema::hasColumn('vehicles', 'mileage')) {
        $table->dropColumn('mileage');
    }
});
```

### Step 7: Run All Migrations
```bash
php artisan migrate
```

---

## Create Models

### Step 8: Create Variant Model
```bash
php artisan make:model Variant
```
Then edit `app/Models/Variant.php` and replace with:
```php
<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use CachedLookup;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
```

### Step 9: Create Euronom Model
```bash
php artisan make:model Euronom
```
Then edit `app/Models/Euronom.php` and replace with:
```php
<?php

namespace App\Models;

use App\Traits\CachedLookup;
use Illuminate\Database\Eloquent\Model;

class Euronom extends Model
{
    use CachedLookup;

    public $timestamps = false;

    protected $table = 'euronorms';
    
    protected $fillable = [
        'name',
    ];
}
```

---

## Update Existing Models

### Step 10: Update VehicleDetail Model
Edit `app/Models/VehicleDetail.php`:

1. Add to `$fillable` array:
```php
'variant_id',
'euronom_id',
'servicebog',
```

2. Remove from `$fillable`:
```php
// Remove: 'euronorm',
```

3. Remove from `$casts`:
```php
// Remove: 'euronorm',
```

4. Add relationships:
```php
public function variant(): BelongsTo
{
    return $this->belongsTo(Variant::class);
}

public function euronom(): BelongsTo
{
    return $this->belongsTo(Euronom::class);
}
```

5. Add accessors:
```php
public function getVariantNameAttribute(): ?string
{
    return self::getCachedLookup('variants', $this->variant_id);
}

public function getEuronomNameAttribute(): ?string
{
    return self::getCachedLookup('euronorms', $this->euronom_id);
}
```

### Step 11: Update Vehicle Model
Edit `app/Models/Vehicle.php`:

1. Remove from `$fillable`:
```php
// Remove: 'mileage',
```

2. Remove from `$casts`:
```php
// Remove: 'mileage' => 'integer',
```

3. Update `getTitleAttribute()` method:
```php
public function getTitleAttribute($value): ?string
{
    if (!empty($value)) {
        return $value;
    }
    
    $parts = array_filter([
        $this->getAttribute('brand_name'),
        $this->getAttribute('model_name'),
        $this->getAttribute('model_year_name'),
        $this->getAttribute('fuel_type_name'),
    ]);
    
    return !empty($parts) ? implode(' ', $parts) : null;
}
```

### Step 12: Update User Model
Edit `app/Models/User.php`:

Add to `$fillable` array:
```php
'address',
'postcode',
```

---

## Update Controller

### Step 13: Update SellYourCarController
Edit `app/Http/Controllers/SellYourCarController.php`:

1. Add imports at the top:
```php
use App\Models\Variant;
use App\Models\Euronom;
```

2. Remove duplicate imports (if any):
```php
// Remove duplicate: use App\Models\Brand;
// Remove duplicate: use App\Models\VehicleModel;
// Remove duplicate: use App\Models\ModelYear;
// Remove duplicate: use App\Models\FuelType;
// Remove duplicate: use App\Models\Equipment;
```

3. In `show()` method, add to `$lookupData`:
```php
'variants' => Variant::orderBy('name')->get(),
'euronorms' => Euronom::orderBy('name')->get(),
'plans' => Plan::where('is_active', true)->with(['planFeatures.feature'])->orderBy('name')->get(),
```

4. In `store()` method, add validation rules:
```php
'variant_id' => 'nullable|exists:variants,id',
'variant_name' => 'nullable|string|max:100',
'euronom_id' => 'nullable|exists:euronorms,id',
'euronom_name' => 'nullable|string|max:50',
'servicebog' => 'nullable|in:Yes,No,Default',
'seller_address' => 'nullable|string|max:255',
'seller_postcode' => 'nullable|string|max:10',
'first_registration_month' => 'nullable|integer|min:1|max:12',
'first_registration_year' => 'nullable|integer|min:1900|max:2100',
'last_inspection_month' => 'nullable|integer|min:1|max:12',
'last_inspection_year' => 'nullable|integer|min:1900|max:2100',
```

5. In `store()` method, add variant handling:
```php
$variantId = null;
if ($request->has('variant_id') && $request->input('variant_id')) {
    $variantId = $request->input('variant_id');
} elseif ($request->has('variant_name') && $request->input('variant_name')) {
    $variant = Variant::firstOrCreate(['name' => $request->input('variant_name')]);
    $variantId = $variant->id;
}
```

6. In `store()` method, add euronom handling:
```php
$euronomId = null;
if ($request->has('euronom_id') && $request->input('euronom_id')) {
    $euronomId = $request->input('euronom_id');
} elseif ($request->has('euronom_name') && $request->input('euronom_name')) {
    $euronom = Euronom::firstOrCreate(['name' => $request->input('euronom_name')]);
    $euronomId = $euronom->id;
}
```

7. In `store()` method, add month/year to date conversion:
```php
// First Registration Date
if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
    $month = $request->input('first_registration_month');
    $year = $request->input('first_registration_year');
    $firstRegistrationDate = sprintf('%04d-%02d-01', $year, $month);
} elseif ($request->has('first_registration_date')) {
    $firstRegistrationDate = $request->input('first_registration_date');
} else {
    $firstRegistrationDate = null;
}

// Last Inspection Date
$lastInspectionDate = null;
if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
    $month = $request->input('last_inspection_month');
    $year = $request->input('last_inspection_year');
    $lastInspectionDate = sprintf('%04d-%02d-01', $year, $month);
} elseif ($request->has('last_inspection_date')) {
    $lastInspectionDate = $request->input('last_inspection_date');
}
```

8. In `store()` method, add auto-generate title:
```php
$title = $request->input('title');
if (empty($title) && $request->has('brand_id') && $request->has('model_id') && 
    $request->has('model_year_id') && $request->has('fuel_type_id')) {
    $title = $this->generateTitle(
        $request->input('brand_id'),
        $request->input('model_id'),
        $request->input('model_year_id'),
        $request->input('fuel_type_id')
    );
}
```

9. In `store()` method, add auto-generate description:
```php
$description = $request->input('description');
if (empty($description)) {
    $description = $this->generateDescription($request, $variantId, $euronomId);
}
```

10. In `store()` method, add to `$detailsFields` array:
```php
'variant_id',
'euronom_id',
'servicebog',
```

11. In `store()` method, add variant_id, euronom_id, last_inspection_date, description to vehicleDetailsData:
```php
if ($variantId) {
    $vehicleDetailsData['variant_id'] = $variantId;
}
if ($euronomId) {
    $vehicleDetailsData['euronom_id'] = $euronomId;
}
if ($lastInspectionDate) {
    $vehicleDetailsData['last_inspection_date'] = $lastInspectionDate;
}
if ($description) {
    $vehicleDetailsData['description'] = $description;
}
```

12. In `store()` method, add user address/postcode update:
```php
if ($request->has('seller_address') || $request->has('seller_postcode')) {
    $userUpdate = [];
    if ($request->has('seller_address')) {
        $userUpdate['address'] = $request->input('seller_address');
    }
    if ($request->has('seller_postcode')) {
        $userUpdate['postcode'] = $request->input('seller_postcode');
    }
    $user->update($userUpdate);
}
```

13. Add helper methods to the controller:
```php
private function generateTitle(?int $brandId, ?int $modelId, ?int $modelYearId, ?int $fuelTypeId): ?string
{
    $parts = [];
    
    if ($brandId) {
        $brand = Brand::find($brandId);
        if ($brand) $parts[] = $brand->name;
    }
    
    if ($modelId) {
        $model = VehicleModel::find($modelId);
        if ($model) $parts[] = $model->name;
    }
    
    if ($modelYearId) {
        $modelYear = ModelYear::find($modelYearId);
        if ($modelYear) $parts[] = $modelYear->name;
    }
    
    if ($fuelTypeId) {
        $fuelType = FuelType::find($fuelTypeId);
        if ($fuelType) $parts[] = $fuelType->name;
    }
    
    return !empty($parts) ? implode(' ', $parts) : null;
}

private function generateDescription(Request $request, ?int $variantId, ?int $euronomId): string
{
    $descriptionParts = [];
    
    // Equipment
    if ($request->has('equipment_ids') && is_array($request->input('equipment_ids'))) {
        $equipmentIds = $request->input('equipment_ids');
        if (!empty($equipmentIds)) {
            $equipments = Equipment::whereIn('id', $equipmentIds)->pluck('name')->toArray();
            if (!empty($equipments)) {
                $descriptionParts[] = 'Equipment: ' . implode(', ', $equipments);
            }
        }
    }
    
    // Servicebog
    if ($request->has('servicebog') && $request->input('servicebog') && $request->input('servicebog') !== 'Default') {
        $descriptionParts[] = 'Service book: ' . $request->input('servicebog');
    }
    
    // Kilometer Driven
    if ($request->has('km_driven') && $request->input('km_driven')) {
        $descriptionParts[] = 'Kilometers driven: ' . number_format($request->input('km_driven'), 0, ',', '.') . ' km';
    }
    
    // First Registration
    if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
        $month = $request->input('first_registration_month');
        $year = $request->input('first_registration_year');
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $descriptionParts[] = 'First registration: ' . $monthName . ' ' . $year;
    }
    
    // Last Inspection
    if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
        $month = $request->input('last_inspection_month');
        $year = $request->input('last_inspection_year');
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $descriptionParts[] = 'Last inspection: ' . $monthName . ' ' . $year;
    }
    
    // KM/L (Fuel Efficiency)
    if ($request->has('fuel_efficiency') && $request->input('fuel_efficiency')) {
        $descriptionParts[] = 'Fuel efficiency: ' . number_format($request->input('fuel_efficiency'), 2) . ' km/l';
    }
    
    // Euronom
    if ($euronomId) {
        $euronom = Euronom::find($euronomId);
        if ($euronom) {
            $descriptionParts[] = 'Euro norm: ' . $euronom->name;
        }
    }
    
    // Total Technical Weight
    if ($request->has('technical_total_weight') && $request->input('technical_total_weight')) {
        $descriptionParts[] = 'Total technical weight: ' . number_format($request->input('technical_total_weight'), 0, ',', '.') . ' kg';
    }
    
    return implode('. ', $descriptionParts) . '.';
}
```

---

## Update Service

### Step 14: Update VehicleService
Edit `app/Services/VehicleService.php`:

1. Add imports:
```php
use App\Models\Variant;
use App\Models\Euronom;
```

2. In `transformNummerpladeData()` method, remove mileage mapping:
```php
// Remove these lines:
// if (isset($apiData['mileage'])) {
//     $transformed['mileage'] = $apiData['mileage'];
//     $transformed['km_driven'] = $apiData['mileage'];
// }
```

3. In `transformNummerpladeData()` method, add variant handling:
```php
// Lookup variant_id from variants table
if (isset($apiData['variant'])) {
    $variantName = $apiData['variant'];
    $variant = Variant::firstOrCreate(['name' => $variantName]);
    $transformed['variant_id'] = $variant->id;
}
```

4. In `transformNummerpladeData()` method, add euronom handling:
```php
// Lookup euronom_id from euronorms table
if (isset($apiData['euronorm'])) {
    $euronomName = $apiData['euronorm'];
    $euronom = Euronom::firstOrCreate(['name' => $euronomName]);
    $transformed['euronom_id'] = $euronom->id;
}
```

5. In `createVehicle()` and `updateVehicle()` methods, add to `$detailsFields` array:
```php
'variant_id',
'euronom_id',
'servicebog',
```

---

## Update View

### Step 15: Update sell-your-car.blade.php
Edit `resources/views/sell-your-car.blade.php`:

1. Restructure form sections (1-15) as expandable sections
2. Add Title field (read-only, auto-generated):
```blade
<input type="text" id="title" name="title" readonly
    class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm cursor-not-allowed"
    placeholder="Auto-generated from vehicle details">
<p class="field-help">Auto-generated from brand + model + model year + fuel type</p>
```

3. Add Variant dropdown:
```blade
<select id="variant_id" name="variant_id"
    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
    <option value="">Select Variant</option>
    @foreach($lookupData['variants'] as $variant)
        <option value="{{ $variant->id }}">{{ $variant->name }}</option>
    @endforeach
</select>
```

4. Add Servicebog radio buttons (styled as buttons):
```blade
<div class="flex gap-3">
    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
        <input type="radio" name="servicebog" value="Yes" class="h-4 w-4 text-primary">
        <span>Yes</span>
    </label>
    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
        <input type="radio" name="servicebog" value="No" class="h-4 w-4 text-primary">
        <span>No</span>
    </label>
    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
        <input type="radio" name="servicebog" value="Default" checked class="h-4 w-4 text-primary">
        <span>Default</span>
    </label>
</div>
```

5. Add CSS for servicebog radio buttons:
```css
.servicebog-radio {
    transition: all 0.2s ease;
}
.servicebog-radio:hover {
    background: var(--accent);
    border-color: var(--primary);
}
.servicebog-radio:has(input[type="radio"]:checked) {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--primary-foreground);
}
.servicebog-radio:has(input[type="radio"]:checked) span {
    color: var(--primary-foreground);
    font-weight: 600;
}
```

6. Add month/year pickers for First Registration:
```blade
<select id="first_registration_month" name="first_registration_month">
    <option value="">Select Month</option>
    @for($i = 1; $i <= 12; $i++)
        <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
    @endfor
</select>
<select id="first_registration_year" name="first_registration_year">
    <option value="">Select Year</option>
    @for($i = date('Y'); $i >= 1900; $i--)
        <option value="{{ $i }}">{{ $i }}</option>
    @endfor
</select>
```

7. Add month/year pickers for Last Inspection (similar to above)

8. Add Euronom dropdown:
```blade
<select id="euronom_id" name="euronom_id">
    <option value="">Select Euronom</option>
    @foreach($lookupData['euronorms'] as $euronom)
        <option value="{{ $euronom->id }}">{{ $euronom->name }}</option>
    @endforeach
</select>
```

9. Add Seller Information section:
```blade
<input type="text" id="seller_name" name="seller_name" value="{{ $user->name ?? '' }}">
<input type="text" id="seller_phone" name="seller_phone" value="{{ $user->phone ?? '' }}">
<textarea id="seller_address" name="seller_address">{{ $user->address ?? '' }}</textarea>
<input type="text" id="seller_postcode" name="seller_postcode" value="{{ $user->postcode ?? '' }}">
```

10. Add Packages section:
```blade
@foreach($lookupData['plans'] as $plan)
    <label>
        <input type="radio" name="plan_id" value="{{ $plan->id }}">
        <div>{{ $plan->name }}</div>
        @if($plan->planFeatures)
            <ul>
                @foreach($plan->planFeatures as $planFeature)
                    <li>{{ $planFeature->feature->description ?? $planFeature->feature->key }}: {{ $planFeature->value }}</li>
                @endforeach
            </ul>
        @endif
    </label>
@endforeach
```

---

## Update JavaScript

### Step 16: Update sell-your-car-form.js
Edit `public/js/sell-your-car-form.js`:

1. Fix API response parsing to handle nested structure:
```javascript
// In initRegistrationLookup() function, update the data extraction:
if (data.data && data.data.data && typeof data.data.data === 'object') {
    vehicleData = data.data.data;
} else if (data.data && typeof data.data === 'object' && !Array.isArray(data.data) && data.data.registration) {
    vehicleData = data.data;
}
```

2. Fix setSelectByIdOrText() function:
```javascript
function setSelectByIdOrText(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select || select.tagName !== 'SELECT') {
        console.warn(`Select not found or invalid: ${selectId}`);
        return false;
    }
    
    // ... existing ID checks ...
    
    // Convert HTMLOptionsCollection to array for safe iteration
    const options = Array.from(select.options);
    for (let option of options) {
        if (option.value && option.text && option.text.trim().toLowerCase() === text) {
            select.value = option.value;
            return true;
        }
    }
    
    return false;
}
```

3. Add variant handling in prefillForm():
```javascript
const variant = apiData.variant || apiData.variantName || apiData.variant_name;
if (variant) {
    if (!setSelectByIdOrText('variant_id', variant)) {
        const vehicleForm = document.getElementById('vehicle-form');
        let variantHiddenInput = document.getElementById('variant_name_hidden');
        if (!variantHiddenInput && vehicleForm) {
            variantHiddenInput = document.createElement('input');
            variantHiddenInput.type = 'hidden';
            variantHiddenInput.id = 'variant_name_hidden';
            variantHiddenInput.name = 'variant_name';
            vehicleForm.appendChild(variantHiddenInput);
        }
        if (variantHiddenInput) {
            variantHiddenInput.value = typeof variant === 'object' ? variant.name : variant;
        }
    }
}
```

4. Add euronom handling in prefillForm():
```javascript
const euronom = apiData.euronorm || apiData.euronom || apiData.euroNorm || apiData.euro_norm;
if (euronom) {
    if (!setSelectByIdOrText('euronom_id', euronom)) {
        const vehicleForm = document.getElementById('vehicle-form');
        let euronomHiddenInput = document.getElementById('euronom_name_hidden');
        if (!euronomHiddenInput && vehicleForm) {
            euronomHiddenInput = document.createElement('input');
            euronomHiddenInput.type = 'hidden';
            euronomHiddenInput.id = 'euronom_name_hidden';
            euronomHiddenInput.name = 'euronom_name';
            vehicleForm.appendChild(euronomHiddenInput);
        }
        if (euronomHiddenInput) {
            euronomHiddenInput.value = typeof euronom === 'object' ? euronom.name : euronom;
        }
    }
}
```

5. Add month/year date handling:
```javascript
// First Registration
const regDate = apiData.firstRegistrationDate || apiData.first_registration_date;
if (regDate) {
    try {
        const date = new Date(regDate);
        if (!isNaN(date.getTime())) {
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            setFieldValue('first_registration_month', month);
            setFieldValue('first_registration_year', year);
        }
    } catch (e) {
        console.warn('Invalid date format:', regDate);
    }
}

// Last Inspection
const lastInspectionDate = apiData.last_inspection_date || apiData.lastInspectionDate;
if (lastInspectionDate) {
    try {
        const date = new Date(lastInspectionDate);
        if (!isNaN(date.getTime())) {
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            setFieldValue('last_inspection_month', month);
            setFieldValue('last_inspection_year', year);
        }
    } catch (e) {
        console.warn('Invalid last inspection date format:', lastInspectionDate);
    }
}
```

6. Add auto-generation functions:
```javascript
function generateTitle() {
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');
    const modelYearSelect = document.getElementById('model_year_id');
    const fuelTypeSelect = document.getElementById('fuel_type_id');
    const titleInput = document.getElementById('title');
    
    if (!titleInput) return;
    
    const parts = [];
    if (brandSelect && brandSelect.value) {
        parts.push(brandSelect.options[brandSelect.selectedIndex].text);
    }
    if (modelSelect && modelSelect.value) {
        parts.push(modelSelect.options[modelSelect.selectedIndex].text);
    }
    if (modelYearSelect && modelYearSelect.value) {
        parts.push(modelYearSelect.options[modelYearSelect.selectedIndex].text);
    }
    if (fuelTypeSelect && fuelTypeSelect.value) {
        parts.push(fuelTypeSelect.options[fuelTypeSelect.selectedIndex].text);
    }
    
    if (parts.length > 0) {
        titleInput.value = parts.join(' ');
    }
}

function generateDescription() {
    const descriptionTextarea = document.getElementById('description');
    if (!descriptionTextarea || descriptionTextarea.dataset.userEdited === 'true') {
        return;
    }
    
    const parts = [];
    
    // Add equipment, servicebog, km_driven, dates, fuel_efficiency, euronom, technical_total_weight
    // ... (implement based on requirements)
    
    if (parts.length > 0) {
        descriptionTextarea.value = parts.join('. ') + '.';
    }
}
```

---

## Testing Commands

### Step 17: Test the Implementation
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Test the form
# 1. Visit /sell-your-car
# 2. Enter a registration number
# 3. Verify all fields populate correctly
# 4. Submit the form
# 5. Verify data is saved correctly in database
```

---

## Quick Reference

### Field Mappings
- Registration → `vehicles.registration`
- Title → `vehicles.title` (auto-generated)
- Variant → `vehicle_details.variant_id` (from `variants` table)
- Servicebog → `vehicle_details.servicebog` (enum: yes/no/default)
- Kilometer Driven → `vehicles.km_driven`
- First Registration → `vehicles.first_registration_date` (month/year)
- Last Inspection → `vehicle_details.last_inspection_date` (month/year)
- KM/L → `vehicles.fuel_efficiency`
- Euronom → `vehicle_details.euronom_id` (from `euronorms` table)
- Total Technical Weight → `vehicle_details.technical_total_weight`
- Description → `vehicle_details.description` (auto-generated)
- Seller Info → `users.name`, `users.phone`, `users.address`, `users.postcode`
- Price → `vehicles.price`

### Important Notes
- Model class: `Euronom` (singular)
- Table name: `euronorms` (plural)
- Removed: `mileage` column from `vehicles` table
- Changed: `euronorm` string → `euronom_id` foreign key

---

Generated: 2026-01-13
