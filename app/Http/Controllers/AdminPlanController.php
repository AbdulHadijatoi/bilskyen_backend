<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Feature;
use App\Models\PlanAvailability;
use App\Models\PlanPriceHistory;
use App\Constants\BillingModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin Plan Controller
 */
class AdminPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::with(['features.featureValueType', 'availability.allowedRole', 'availability.dealer', 'priceHistory' => function($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now())->orderBy('starts_at', 'desc');
        }])->paginate($request->get('limit', 15));

        return $this->paginated($plans);
    }

    public function show(int $id): JsonResponse
    {
        $plan = Plan::with(['features.featureValueType', 'availability.allowedRole', 'availability.dealer', 'priceHistory'])->findOrFail($id);
        return $this->success($plan);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'trial_days' => 'sometimes|integer|min:0|nullable',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
            'dealer_ids' => 'sometimes|array',
            'dealer_ids.*' => 'exists:dealers,id',
            'pricing' => 'sometimes|array',
            'pricing.monthly_price' => 'sometimes|integer|min:0',
            'pricing.yearly_price' => 'sometimes|integer|min:0',
            'pricing.currency' => 'sometimes|string|size:3',
            'billing_model' => 'sometimes|string|in:'.BillingModel::SUBSCRIPTION.','.BillingModel::USAGE_DAILY,
            'price_per_listing_per_day' => 'sometimes|integer|min:0|nullable',
        ]);

        // Validate that at least one of role_ids or dealer_ids is provided
        if (!$request->has('role_ids') && !$request->has('dealer_ids')) {
            return $this->error(__('messages.api.plan_roles_or_dealers_required'), [], 422);
        }

        DB::beginTransaction();
        try {
            // Create plan
            $plan = Plan::create($request->only([
                'name', 'slug', 'description', 'is_active', 'trial_days',
                'billing_model', 'price_per_listing_per_day',
            ]));

            // Create plan availability records
            $availabilityRecords = [];
            
            // For roles
            if ($request->has('role_ids')) {
                foreach ($request->role_ids as $roleId) {
                    $availabilityRecords[] = [
                        'plan_id' => $plan->id,
                        'allowed_role_id' => $roleId,
                        'dealer_id' => null,
                        'is_enabled' => true,
                        'created_at' => now(),
                    ];
                }
            }
            
            // For dealers
            if ($request->has('dealer_ids')) {
                foreach ($request->dealer_ids as $dealerId) {
                    $availabilityRecords[] = [
                        'plan_id' => $plan->id,
                        'allowed_role_id' => null,
                        'dealer_id' => $dealerId,
                        'is_enabled' => true,
                        'created_at' => now(),
                    ];
                }
            }
            
            if (!empty($availabilityRecords)) {
                PlanAvailability::insert($availabilityRecords);
            }

            // Create price history entries
            if ($request->has('pricing')) {
                $priceRecords = [];
                $pricing = $request->pricing;
                $currency = $pricing['currency'] ?? 'DKK';
                
                if (isset($pricing['monthly_price'])) {
                    $priceRecords[] = [
                        'plan_id' => $plan->id,
                        'price' => $pricing['monthly_price'],
                        'currency' => $currency,
                        'billing_cycle' => 'monthly',
                        'starts_at' => now(),
                        'ends_at' => null,
                    ];
                }
                
                if (isset($pricing['yearly_price'])) {
                    $priceRecords[] = [
                        'plan_id' => $plan->id,
                        'price' => $pricing['yearly_price'],
                        'currency' => $currency,
                        'billing_cycle' => 'yearly',
                        'starts_at' => now(),
                        'ends_at' => null,
                    ];
                }
                
                if (!empty($priceRecords)) {
                    PlanPriceHistory::insert($priceRecords);
                }
            }

            DB::commit();
            
            // Reload plan with relationships
            $plan->load(['features', 'availability.allowedRole', 'availability.dealer', 'priceHistory']);

        return $this->created($plan);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('messages.api.plan_create_failed', ['message' => $e->getMessage()]), [], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:plans,slug,' . $id,
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'trial_days' => 'sometimes|integer|min:0|nullable',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
            'dealer_ids' => 'sometimes|array',
            'dealer_ids.*' => 'exists:dealers,id',
            'pricing' => 'sometimes|array',
            'pricing.monthly_price' => 'sometimes|integer|min:0',
            'pricing.yearly_price' => 'sometimes|integer|min:0',
            'pricing.currency' => 'sometimes|string|size:3',
            'billing_model' => 'sometimes|string|in:'.BillingModel::SUBSCRIPTION.','.BillingModel::USAGE_DAILY,
            'price_per_listing_per_day' => 'sometimes|integer|min:0|nullable',
        ]);

        DB::beginTransaction();
        try {
            // Update plan basic info
            $plan->update($request->only([
                'name', 'slug', 'description', 'is_active', 'trial_days',
                'billing_model', 'price_per_listing_per_day',
            ]));

            // Update plan availability if provided
            if ($request->has('role_ids') || $request->has('dealer_ids')) {
                // Delete existing availability records
                $plan->availability()->delete();
                
                // Create new availability records
                $availabilityRecords = [];
                
                // For roles
                if ($request->has('role_ids')) {
                    foreach ($request->role_ids as $roleId) {
                        $availabilityRecords[] = [
                            'plan_id' => $plan->id,
                            'allowed_role_id' => $roleId,
                            'dealer_id' => null,
                            'is_enabled' => true,
                            'created_at' => now(),
                        ];
                    }
                }
                
                // For dealers
                if ($request->has('dealer_ids')) {
                    foreach ($request->dealer_ids as $dealerId) {
                        $availabilityRecords[] = [
                            'plan_id' => $plan->id,
                            'allowed_role_id' => null,
                            'dealer_id' => $dealerId,
                            'is_enabled' => true,
                            'created_at' => now(),
                        ];
                    }
                }
                
                if (!empty($availabilityRecords)) {
                    PlanAvailability::insert($availabilityRecords);
                }
            }

            // Update pricing if provided
            if ($request->has('pricing')) {
                $pricing = $request->pricing;
                $currency = $pricing['currency'] ?? 'DKK';
                $priceRecords = [];
                
                // End current pricing
                PlanPriceHistory::where('plan_id', $plan->id)
                    ->whereNull('ends_at')
                    ->update(['ends_at' => now()]);
                
                // Create new pricing entries
                if (isset($pricing['monthly_price'])) {
                    $priceRecords[] = [
                        'plan_id' => $plan->id,
                        'price' => $pricing['monthly_price'],
                        'currency' => $currency,
                        'billing_cycle' => 'monthly',
                        'starts_at' => now(),
                        'ends_at' => null,
                    ];
                }
                
                if (isset($pricing['yearly_price'])) {
                    $priceRecords[] = [
                        'plan_id' => $plan->id,
                        'price' => $pricing['yearly_price'],
                        'currency' => $currency,
                        'billing_cycle' => 'yearly',
                        'starts_at' => now(),
                        'ends_at' => null,
                    ];
                }
                
                if (!empty($priceRecords)) {
                    PlanPriceHistory::insert($priceRecords);
                }
            }

            DB::commit();
            
            // Reload plan with relationships
            $plan->load(['features', 'availability.allowedRole', 'availability.dealer', 'priceHistory']);

        return $this->success($plan);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('messages.api.plan_update_failed', ['message' => $e->getMessage()]), [], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->delete(); // Soft delete

        return $this->noContent();
    }

    public function getFeatures(int $id): JsonResponse
    {
        $plan = Plan::with('features')->findOrFail($id);
        return $this->success($plan->features);
    }

    public function assignFeature(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'value' => 'required',
        ]);

        $plan = Plan::findOrFail($id);
        $feature = Feature::findOrFail((int) $validated['feature_id']);

        $normalizedValue = $this->normalizeFeatureValue(
            (int) $feature->feature_value_type_id,
            $validated['value']
        );
        if ($normalizedValue === null) {
            return $this->error(__('messages.api.invalid_feature_value'), [], 422);
        }

        $plan->features()->syncWithoutDetaching([
            $feature->id => ['value' => $normalizedValue]
        ]);

        return $this->success(['message' => __('messages.errors.feature_assigned_success')]);
    }

    private function normalizeFeatureValue(int $featureTypeId, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($featureTypeId === 1) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_int($value) || is_float($value)) {
                if ((float) $value === 1.0) {
                    return 'true';
                }
                if ((float) $value === 0.0) {
                    return 'false';
                }
                return null;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['true', '1'], true)) {
                    return 'true';
                }
                if (in_array($normalized, ['false', '0'], true)) {
                    return 'false';
                }
            }

            return null;
        }

        if ($featureTypeId === 2) {
            if (is_numeric($value)) {
                return trim((string) $value);
            }

            return null;
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return null;
    }

    public function removeFeature(int $id, int $featureId): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->features()->detach($featureId);

        return $this->noContent();
    }

    /**
     * Get plan availability (roles and dealers)
     */
    public function getAvailability(int $id): JsonResponse
    {
        $plan = Plan::with(['availability.allowedRole', 'availability.dealer'])->findOrFail($id);
        
        $availability = [
            'roles' => $plan->availability->where('allowed_role_id', '!=', null)->pluck('allowedRole')->filter()->values(),
            'dealers' => $plan->availability->where('dealer_id', '!=', null)->pluck('dealer')->filter()->values(),
        ];
        
        return $this->success($availability);
    }

    /**
     * Sync plan availability (roles and/or dealers)
     */
    public function syncAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
            'dealer_ids' => 'sometimes|array',
            'dealer_ids.*' => 'exists:dealers,id',
        ]);

        // Validate that at least one is provided
        if (!$request->has('role_ids') && !$request->has('dealer_ids')) {
            return $this->error(__('messages.api.plan_roles_or_dealers_required'), [], 422);
        }

        $plan = Plan::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete existing availability records
            $plan->availability()->delete();
            
            // Create new availability records
            $availabilityRecords = [];
            
            // For roles
            if ($request->has('role_ids')) {
                foreach ($request->role_ids as $roleId) {
                    $availabilityRecords[] = [
                        'plan_id' => $plan->id,
                        'allowed_role_id' => $roleId,
                        'dealer_id' => null,
                        'is_enabled' => true,
                        'created_at' => now(),
                    ];
                }
            }
            
            // For dealers
            if ($request->has('dealer_ids')) {
                foreach ($request->dealer_ids as $dealerId) {
                    $availabilityRecords[] = [
                        'plan_id' => $plan->id,
                        'allowed_role_id' => null,
                        'dealer_id' => $dealerId,
                        'is_enabled' => true,
                        'created_at' => now(),
                    ];
                }
            }
            
            if (!empty($availabilityRecords)) {
                PlanAvailability::insert($availabilityRecords);
            }

            DB::commit();
            
            // Get updated availability to return
            $plan->load(['availability.allowedRole', 'availability.dealer']);
            $availability = [
                'roles' => $plan->availability->where('allowed_role_id', '!=', null)->pluck('allowedRole')->filter()->values(),
                'dealers' => $plan->availability->where('dealer_id', '!=', null)->pluck('dealer')->filter()->values(),
            ];
            
            return $this->success($availability, 200, __('messages.api.plan_availability_updated'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('messages.api.plan_sync_availability_failed', ['message' => $e->getMessage()]), [], 500);
        }
    }

    /**
     * Get current pricing for a plan
     */
    public function getPricing(int $id): JsonResponse
    {
        $plan = Plan::with(['priceHistory' => function($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        }])->findOrFail($id);
        
        $pricing = [
            'monthly' => $plan->priceHistory->where('billing_cycle', 'monthly')->first(),
            'yearly' => $plan->priceHistory->where('billing_cycle', 'yearly')->first(),
        ];
        
        return $this->success($pricing);
    }

    /**
     * Update plan pricing (creates new price history entries)
     */
    public function updatePricing(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'monthly_price' => 'sometimes|integer|min:0',
            'yearly_price' => 'sometimes|integer|min:0',
            'currency' => 'sometimes|string|size:3',
        ]);

        if (!$request->has('monthly_price') && !$request->has('yearly_price')) {
            return $this->error(__('messages.api.plan_pricing_price_required'), [], 422);
        }

        $plan = Plan::findOrFail($id);
        $currency = $request->currency ?? 'DKK';

        DB::beginTransaction();
        try {
            // End current pricing
            PlanPriceHistory::where('plan_id', $plan->id)
                ->whereNull('ends_at')
                ->update(['ends_at' => now()]);
            
            // Create new pricing entries
            $priceRecords = [];
            
            if ($request->has('monthly_price')) {
                $priceRecords[] = [
                    'plan_id' => $plan->id,
                    'price' => $request->monthly_price,
                    'currency' => $currency,
                    'billing_cycle' => 'monthly',
                    'starts_at' => now(),
                    'ends_at' => null,
                ];
            }
            
            if ($request->has('yearly_price')) {
                $priceRecords[] = [
                    'plan_id' => $plan->id,
                    'price' => $request->yearly_price,
                    'currency' => $currency,
                    'billing_cycle' => 'yearly',
                    'starts_at' => now(),
                    'ends_at' => null,
                ];
            }
            
            if (!empty($priceRecords)) {
                PlanPriceHistory::insert($priceRecords);
            }

            DB::commit();
            
            return $this->success(['message' => __('messages.errors.plan_pricing_updated_success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(__('messages.api.plan_pricing_update_failed', ['message' => $e->getMessage()]), [], 500);
        }
    }
}

