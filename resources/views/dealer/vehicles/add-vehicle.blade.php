@extends('layouts.dealer')

@section('title', 'Add Vehicle - Dealer Panel')

@push('styles')
<style>
    .separator-with-text {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 1.5rem 0;
    }
    .separator-with-text::before,
    .separator-with-text::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid hsl(var(--border));
    }
    .separator-with-text span {
        padding: 0 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: hsl(var(--muted-foreground));
    }
</style>
@endpush

@section('content')
<div class="flex w-full flex-col gap-4">
    <div>
        <h2 class="text-xl font-bold">Add Vehicle</h2>
        <p class="text-muted-foreground max-w-2xl">
            Use the form below to add a new vehicle to your inventory. Ensure all
            details are accurate and complete.
        </p>
    </div>

    <hr class="my-3 border-border">

    <form method="POST" action="/dealer/vehicles" class="flex w-full flex-col gap-3.5" enctype="multipart/form-data">
        @csrf

        <!-- Basic Information -->
        <div class="flex w-full flex-col gap-4 md:grid md:grid-cols-2">
            <div class="separator-with-text col-span-2">
                <span>Basic Information</span>
            </div>

            <div class="space-y-2">
                <label for="inventoryDate" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Inventory Date</label>
                <input type="datetime-local" id="inventoryDate" name="inventoryDate" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <p class="text-sm text-muted-foreground">When was the vehicle added to lot?</p>
            </div>

            <div class="space-y-2">
                <label for="registrationNumber" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Registration Number</label>
                <input type="text" id="registrationNumber" name="registrationNumber" placeholder="e.g., KL 10 AB 1234" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <p class="text-sm text-muted-foreground">Enter the vehicle's registration number as per local regulations.</p>
            </div>

            <div class="space-y-2">
                <label for="vin" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Identification Number (VIN)</label>
                <input type="text" id="vin" name="vin" placeholder="e.g., 1HGCM82633A123456" maxlength="17" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter the 17-character VIN for vehicle tracking.</p>
            </div>

            <div class="space-y-2">
                <label for="engineNumber" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Engine Number</label>
                <input type="text" id="engineNumber" name="engineNumber" placeholder="e.g., PJ12345U123456P" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter the engine number for identification.</p>
            </div>

            <div class="space-y-2">
                <label for="make" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Make</label>
                <select id="make" name="make" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select make...</option>
                    <option value="Toyota">Toyota</option>
                    <option value="Honda">Honda</option>
                    <option value="Maruti">Maruti</option>
                    <option value="Hyundai">Hyundai</option>
                    <option value="Mahindra">Mahindra</option>
                    <option value="Tata">Tata</option>
                    <option value="Ford">Ford</option>
                    <option value="Volkswagen">Volkswagen</option>
                    <option value="BMW">BMW</option>
                    <option value="Mercedes-Benz">Mercedes-Benz</option>
                    <option value="Audi">Audi</option>
                    <option value="Nissan">Nissan</option>
                    <option value="Kia">Kia</option>
                    <option value="MG Motor">MG Motor</option>
                    <option value="Skoda">Skoda</option>
                </select>
                <p class="text-sm text-muted-foreground">Select the manufacturer of the vehicle.</p>
            </div>

            <div class="space-y-2">
                <label for="model" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Model</label>
                <input type="text" id="model" name="model" placeholder="e.g., Corolla" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <p class="text-sm text-muted-foreground">Enter the model name of the vehicle.</p>
            </div>

            <div class="space-y-2">
                <label for="variant" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Model Variant</label>
                <input type="text" id="variant" name="variant" placeholder="e.g., Classic Edition (CE)" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter the specific variant of the vehicle model.</p>
            </div>

            <div class="space-y-2">
                <label for="year" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Model Year</label>
                <select id="year" name="year" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select year...</option>
                    @for($y = date('Y'); $y >= 2000; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <p class="text-sm text-muted-foreground">Select the year the vehicle was manufactured.</p>
            </div>

            <div class="space-y-2">
                <label for="vehicleType" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Type</label>
                <select id="vehicleType" name="vehicleType" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select type...</option>
                    <option value="Sedan">Sedan</option>
                    <option value="SUV">SUV</option>
                    <option value="Hatchback">Hatchback</option>
                    <option value="MUV">MUV</option>
                    <option value="Coupe">Coupe</option>
                    <option value="Convertible">Convertible</option>
                    <option value="Pickup Truck">Pickup Truck</option>
                    <option value="Crossover">Crossover</option>
                    <option value="Compact SUV">Compact SUV</option>
                    <option value="Compact Sedan">Compact Sedan</option>
                </select>
                <p class="text-sm text-muted-foreground">Choose the general classification of the vehicle.</p>
            </div>

            <div class="space-y-2">
                <label for="odometer" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Odometer Reading</label>
                <input type="number" id="odometer" name="odometer" placeholder="e.g., 75000" min="0" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <p class="text-sm text-muted-foreground">Enter the current mileage on the vehicle.</p>
            </div>

            <div class="space-y-2">
                <label for="status" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Status</label>
                <select id="status" name="status" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="Pending Purchase">Pending Purchase</option>
                    <option value="Available">Available</option>
                    <option value="Pending">Pending</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Sold">Sold</option>
                    <option value="Not Available">Not Available</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Service">Service</option>
                </select>
                <p class="text-sm text-muted-foreground">Select the current status of the vehicle.</p>
            </div>
        </div>

        <!-- Specifications -->
        <div class="flex w-full flex-col gap-4 md:grid md:grid-cols-2">
            <div class="separator-with-text col-span-2">
                <span>Specifications</span>
            </div>

            <div class="space-y-2">
                <label for="transmissionType" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Transmission Type</label>
                <select id="transmissionType" name="transmissionType" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select transmission...</option>
                    <option value="Manual">Manual</option>
                    <option value="Automatic">Automatic</option>
                    <option value="CVT">CVT</option>
                    <option value="AMT">AMT</option>
                    <option value="DCT">DCT</option>
                </select>
                <p class="text-sm text-muted-foreground">Select the type of transmission.</p>
            </div>

            <div class="space-y-2">
                <label for="fuelType" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Fuel Type</label>
                <select id="fuelType" name="fuelType" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select fuel type...</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Electric">Electric</option>
                    <option value="Hybrid">Hybrid</option>
                    <option value="CNG">CNG</option>
                    <option value="LPG">LPG</option>
                </select>
                <p class="text-sm text-muted-foreground">Choose the fuel the vehicle uses.</p>
            </div>

            <div class="space-y-2">
                <label for="color" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Color</label>
                <input type="text" id="color" name="color" placeholder="e.g., White" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter the exterior color of the vehicle.</p>
            </div>
        </div>

        <!-- History -->
        <div class="flex w-full flex-col gap-4 md:grid md:grid-cols-2">
            <div class="separator-with-text col-span-2">
                <span>History</span>
            </div>

            <div class="space-y-2">
                <label for="ownershipCount" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Number of Ownerships</label>
                <input type="number" id="ownershipCount" name="ownershipCount" placeholder="e.g., 3" min="1" max="999" value="1" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                <p class="text-sm text-muted-foreground">Enter the number of previous owners of the vehicle.</p>
            </div>

            <div class="flex items-center">
                <div class="bg-input/30 flex w-full flex-row items-start space-y-0 space-x-3 rounded-md border p-4">
                    <input type="checkbox" id="accidentHistory" name="accidentHistory" value="1" class="peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <div class="space-y-1 leading-none">
                        <label for="accidentHistory" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Accident History</label>
                        <p class="text-sm text-muted-foreground">Has the vehicle been in any accidents?</p>
                    </div>
                </div>
            </div>

            <div class="space-y-2 col-span-2">
                <label for="condition" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Condition</label>
                <select id="condition" name="condition" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                    <option value="">Select condition...</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Very Good">Very Good</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
                <p class="text-sm text-muted-foreground">Select the overall condition of the vehicle.</p>
            </div>

            <div class="space-y-2 col-span-2">
                <label for="blacklistFlags" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Blacklist Flags</label>
                <select id="blacklistFlags" name="blacklistFlags[]" multiple class="flex min-h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <option value="Stolen">Stolen</option>
                    <option value="Finance Pending">Finance Pending</option>
                    <option value="Legal Issues">Legal Issues</option>
                    <option value="Insurance Claim">Insurance Claim</option>
                </select>
                <p class="text-sm text-muted-foreground">Select any blacklist flags associated with this vehicle (hold Ctrl/Cmd to select multiple).</p>
            </div>
        </div>

        <!-- Display Details -->
        <div class="flex w-full flex-col gap-4">
            <div class="separator-with-text">
                <span>Display Details</span>
            </div>

            <div class="space-y-2">
                <label for="listingPrice" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Listing Price</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">kr.</span>
                    <input type="number" id="listingPrice" name="listingPrice" placeholder="e.g., 1075000" min="0" step="1000" class="flex h-10 w-full rounded-md border border-input bg-background pl-8 pr-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                </div>
                <p class="text-sm text-muted-foreground">Enter the price at which the vehicle is listed for sale.</p>
            </div>

            <div class="space-y-2">
                <label for="features" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Features</label>
                <input type="text" id="features" name="features" placeholder="Enter features separated by commas (e.g., Sunroof, Leather Seats, ABS)" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter vehicle features separated by commas.</p>
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Enter a detailed description of the vehicle..." class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                <p class="text-sm text-muted-foreground">Provide a detailed description of the vehicle for potential buyers.</p>
            </div>

            <div class="space-y-2">
                <label for="images" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Vehicle Images</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Upload images of the vehicle (multiple images allowed).</p>
            </div>
        </div>

        <!-- For Dealer Only -->
        <div class="flex w-full flex-col gap-4">
            <div class="separator-with-text">
                <span>For Dealer Only</span>
            </div>

            <div class="space-y-2">
                <label for="pendingWorks" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Enter pending works on this vehicle.</label>
                <input type="text" id="pendingWorks" name="pendingWorks" placeholder="Enter pending works separated by commas (e.g., 'Replace brake pads', 'Change oil')" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <p class="text-sm text-muted-foreground">Enter any pending works that need to be done on this vehicle.</p>
            </div>

            <div class="space-y-2">
                <label for="remarks" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3" placeholder="Enter internal remarks about this vehicle..." class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                <p class="text-sm text-muted-foreground">Enter any internal remarks or notes about this vehicle (not visible to customers).</p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex w-full items-center justify-end gap-3">
            <button type="reset" class="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                Reset
            </button>
            <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]">
                Save Vehicle
            </button>
        </div>
    </form>
</div>
@endsection

