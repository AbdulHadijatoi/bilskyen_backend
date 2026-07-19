<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleImportController;
use App\Http\Controllers\BilbasenVehicleImportController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\DealerProfileController;
use App\Http\Controllers\DealerStaffController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DealerLookupController;
use App\Http\Controllers\DealerDashboardController;
use App\Http\Controllers\DealerEnquiryController;
use App\Http\Controllers\DealerAuditLogController;
use App\Http\Controllers\DealerAnalyticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\LeadCrmController;
use App\Http\Controllers\DealerOnboardingController;
use App\Http\Controllers\DealerBillingController;
use App\Http\Controllers\DealerAiController;
use App\Http\Controllers\DealerFeedController;
use App\Http\Controllers\DealerSyndicationController;

/*
|--------------------------------------------------------------------------
| Dealer API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/dealer/* via bootstrap/app.php
| All routes require auth:api middleware (standardized)
|
*/

// Dealer routes (requires authentication - standardized to auth:api)
Route::middleware('auth:api')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DealerDashboardController::class, 'index'])
        ->middleware('permission:dealer.dashboard.view');

    Route::get('/get-vehicles-overview', [DealerDashboardController::class, 'getVehiclesOverviewWidget'])
        ->middleware('permission:dealer.dashboard.view');

    Route::get('/get-sales-overview', [DealerDashboardController::class, 'getSalesOverviewWidget'])
        ->middleware('permission:dealer.dashboard.view');
    Route::get('/listing-health-attention', [DealerDashboardController::class, 'getListingHealthAttention'])
        ->middleware(['permission:dealer.dashboard.view', 'dealer.feature:listing_health_inbox']);
    Route::get('/market-pulse', [DealerDashboardController::class, 'getMarketPulseWidget'])
        ->middleware(['permission:dealer.dashboard.view', 'dealer.feature:market_pulse']);

    Route::prefix('accounting')->group(function () {
        Route::get('/get-financial-overview', [AccountingController::class, 'getFinancialOverview'])
            ->middleware('permission:dealer.dashboard.view');
        Route::get('/get-financial-overview-chart', [AccountingController::class, 'getFinancialOverviewChart'])
            ->middleware('permission:dealer.dashboard.view');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::get('/count', [NotificationController::class, 'getCount']);
        Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
    });
    
    // Vehicle Management
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'dealerIndex'])
            ->middleware('permission:dealer.vehicles.view');
        
        Route::get('/show/{id}', [VehicleController::class, 'show'])
            ->middleware('permission:dealer.vehicles.view');

        Route::get('/{id}/listing-health', [VehicleController::class, 'listingHealth'])
            ->middleware('permission:dealer.vehicles.view');
        
        Route::post('/', [VehicleController::class, 'store'])
            ->middleware(['throttle:20,1', 'idempotency', 'permission:dealer.vehicles.create']);
        
        Route::post('/draft', [VehicleController::class, 'storeDraft'])
            ->middleware('permission:dealer.vehicles.create');
        
        Route::post('/update/{id}', [VehicleController::class, 'update'])
            ->middleware('permission:dealer.vehicles.update');
        
        Route::post('/bulk-delete', [VehicleController::class, 'bulkDestroy'])
            ->middleware('permission:dealer.vehicles.delete');

        Route::post('/delete/{id}', [VehicleController::class, 'destroy'])
            ->middleware('permission:dealer.vehicles.delete');
        
        Route::post('/update-status/{id}', [VehicleController::class, 'updateStatus'])
            ->middleware('permission:dealer.vehicles.status');

        Route::post('/renew-listing/{id}', [VehicleController::class, 'renewListing'])
            ->middleware('permission:dealer.vehicles.update');

        Route::post('/{id}/3d-view', [VehicleController::class, 'upload3dView'])
            ->middleware('permission:dealer.vehicles.media');

        Route::delete('/{id}/3d-view', [VehicleController::class, 'delete3dView'])
            ->middleware('permission:dealer.vehicles.media');
        
        Route::post('/update-equipment/{id}', [VehicleController::class, 'updateEquipment'])
            ->middleware('permission:dealer.vehicles.update');
        
        Route::post('/{id}/images', [VehicleController::class, 'uploadImages'])
            ->middleware('permission:dealer.vehicles.media');
        
        Route::delete('/{id}/images/{imageId}', [VehicleController::class, 'deleteImage'])
            ->middleware('permission:dealer.vehicles.media');
        
        Route::put('/{id}/price', [VehicleController::class, 'updatePrice'])
            ->middleware('permission:dealer.vehicles.update');

        Route::post('/{id}/apply-suggested-price', [VehicleController::class, 'applySuggestedPrice'])
            ->middleware(['permission:dealer.vehicles.update', 'dealer.feature:listing_health_price_apply']);

        Route::post('/{id}/boost', [VehicleController::class, 'boostListing'])
            ->middleware(['permission:dealer.vehicles.update', 'dealer.feature:listing_boost']);
        
        Route::post('/lookup-by-registration', [VehicleController::class, 'lookupByRegistration'])
            ->middleware(['throttle:40,1', 'permission:dealer.vehicles.create']);

        Route::post('/bulk-price-update', [\App\Http\Controllers\DealerBulkPriceController::class, 'update'])
            ->middleware(['permission:dealer.vehicles.update', 'dealer.feature:bulk_price_update']);

        Route::get('/import/template', [VehicleImportController::class, 'downloadTemplate'])
            ->middleware('permission:dealer.vehicles.create');

        Route::get('/import/sample', [VehicleImportController::class, 'sample'])
            ->middleware('permission:dealer.vehicles.create');

        Route::post('/import', [VehicleImportController::class, 'import'])
            ->middleware(['throttle:10,1', 'permission:dealer.vehicles.create']);

        Route::get('/import/batches', [VehicleImportController::class, 'batches'])
            ->middleware('permission:dealer.vehicles.create');

        Route::get('/import/batches/{id}', [VehicleImportController::class, 'showBatch'])
            ->middleware('permission:dealer.vehicles.create');

        // Bilbasen URL import — keep under /import/* (static paths before /{id} routes)
        Route::post('/import/from-url/preview', [BilbasenVehicleImportController::class, 'preview'])
            ->middleware(['throttle:20,1', 'permission:dealer.vehicles.create']);

        Route::post('/import/from-url', [BilbasenVehicleImportController::class, 'import'])
            ->middleware(['throttle:10,1', 'idempotency', 'permission:dealer.vehicles.create']);

        // Back-compat aliases (older panel builds)
        Route::post('/import-from-url/preview', [BilbasenVehicleImportController::class, 'preview'])
            ->middleware(['throttle:20,1', 'permission:dealer.vehicles.create']);

        Route::post('/import-from-url', [BilbasenVehicleImportController::class, 'import'])
            ->middleware(['throttle:10,1', 'idempotency', 'permission:dealer.vehicles.create']);

        Route::get('/export', [VehicleController::class, 'exportStock'])
            ->middleware('permission:dealer.feeds.export');

        Route::post('/{id}/images/reorder', [VehicleController::class, 'reorderImages'])
            ->middleware('permission:dealer.vehicles.media');

        Route::put('/{id}/video', [VehicleController::class, 'updateVideo'])
            ->middleware('permission:dealer.vehicles.media');
    });

    Route::prefix('feeds')->middleware('dealer.feature:inventory_feeds')->group(function () {
        Route::get('/tokens', [DealerFeedController::class, 'index'])
            ->middleware('permission:dealer.feeds.export');
        Route::post('/tokens', [DealerFeedController::class, 'store'])
            ->middleware('permission:dealer.feeds.export');
        Route::delete('/tokens/{id}', [DealerFeedController::class, 'destroy'])
            ->middleware('permission:dealer.feeds.export');
    });

    Route::prefix('syndication')->middleware('dealer.feature:syndication')->group(function () {
        Route::get('/', [DealerSyndicationController::class, 'index'])
            ->middleware('permission:dealer.syndication.manage');
        Route::put('/', [DealerSyndicationController::class, 'update'])
            ->middleware('permission:dealer.syndication.manage');
        Route::post('/sync', [DealerSyndicationController::class, 'syncNow'])
            ->middleware('permission:dealer.syndication.manage');
    });

    Route::prefix('trade-in')->group(function () {
        Route::get('/', [\App\Http\Controllers\DealerTradeInController::class, 'index'])
            ->middleware('permission:dealer.trade_in.manage');
        Route::put('/{id}', [\App\Http\Controllers\DealerTradeInController::class, 'update'])
            ->middleware('permission:dealer.trade_in.manage');
    });

    Route::prefix('branding')->group(function () {
        Route::get('/', [\App\Http\Controllers\DealerBrandingController::class, 'show'])
            ->middleware('permission:dealer.branding.manage');
        Route::put('/', [\App\Http\Controllers\DealerBrandingController::class, 'update'])
            ->middleware('permission:dealer.branding.manage');
        Route::post('/domains', [\App\Http\Controllers\DealerBrandingController::class, 'addDomain'])
            ->middleware('permission:dealer.branding.manage');
        Route::post('/domains/{id}/verify', [\App\Http\Controllers\DealerBrandingController::class, 'verifyDomain'])
            ->middleware('permission:dealer.branding.manage');
        Route::delete('/domains/{id}', [\App\Http\Controllers\DealerBrandingController::class, 'deleteDomain'])
            ->middleware('permission:dealer.branding.manage');
        Route::get('/audit-link', [\App\Http\Controllers\DealerBrandingController::class, 'auditLink'])
            ->middleware(['permission:dealer.branding.manage', 'dealer.feature:branded_inventory_audit']);
        Route::get('/review-summary', [\App\Http\Controllers\DealerBrandingController::class, 'reviewSummary'])
            ->middleware(['permission:dealer.branding.manage', 'dealer.feature:review_management']);
    });

    Route::prefix('dms')->group(function () {
        Route::get('/', [\App\Http\Controllers\DealerDmsController::class, 'index'])
            ->middleware('permission:dealer.dms.manage');
        Route::post('/api-keys', [\App\Http\Controllers\DealerDmsController::class, 'createApiKey'])
            ->middleware(['permission:dealer.dms.manage', 'dealer.feature:api_access']);
        Route::delete('/api-keys/{id}', [\App\Http\Controllers\DealerDmsController::class, 'deleteApiKey'])
            ->middleware(['permission:dealer.dms.manage', 'dealer.feature:api_access']);
        Route::post('/webhooks', [\App\Http\Controllers\DealerDmsController::class, 'createWebhook'])
            ->middleware(['permission:dealer.dms.manage', 'dealer.feature:dms_sync']);
        Route::delete('/webhooks/{id}', [\App\Http\Controllers\DealerDmsController::class, 'deleteWebhook'])
            ->middleware(['permission:dealer.dms.manage', 'dealer.feature:dms_sync']);
    });

    Route::prefix('marketing/campaigns')->middleware('dealer.feature:marketing_campaigns')->group(function () {
        Route::get('/', [\App\Http\Controllers\DealerMarketingCampaignController::class, 'index'])
            ->middleware('permission:dealer.branding.manage');
        Route::post('/', [\App\Http\Controllers\DealerMarketingCampaignController::class, 'store'])
            ->middleware('permission:dealer.branding.manage');
        Route::put('/{id}', [\App\Http\Controllers\DealerMarketingCampaignController::class, 'update'])
            ->middleware('permission:dealer.branding.manage');
        Route::post('/{id}/send', [\App\Http\Controllers\DealerMarketingCampaignController::class, 'send'])
            ->middleware('permission:dealer.branding.manage');
        Route::delete('/{id}', [\App\Http\Controllers\DealerMarketingCampaignController::class, 'destroy'])
            ->middleware('permission:dealer.branding.manage');
    });

    Route::prefix('leads/{leadId}/deal-quotes')->middleware('dealer.feature:deal_builder')->group(function () {
        Route::get('/', [\App\Http\Controllers\DealerDealQuoteController::class, 'index'])
            ->middleware('permission:dealer.leads.view');
        Route::post('/', [\App\Http\Controllers\DealerDealQuoteController::class, 'store'])
            ->middleware('permission:dealer.leads.view');
        Route::put('/{id}', [\App\Http\Controllers\DealerDealQuoteController::class, 'update'])
            ->middleware('permission:dealer.leads.view');
        Route::post('/{id}/send', [\App\Http\Controllers\DealerDealQuoteController::class, 'send'])
            ->middleware('permission:dealer.leads.view');
        Route::delete('/{id}', [\App\Http\Controllers\DealerDealQuoteController::class, 'destroy'])
            ->middleware('permission:dealer.leads.view');
    });

    Route::get('/compliance/lead-pii-export', [\App\Http\Controllers\DealerComplianceController::class, 'exportLeadPiiAccess'])
        ->middleware('permission:dealer.compliance.export');
    
    // Lookup endpoints (for form dropdowns and vehicle lookup)
    Route::get('/lookup-constants', [DealerLookupController::class, 'getLookupConstants']);
    Route::prefix('lookup')->group(function () {
        Route::post('/vehicle-by-registration', [DealerLookupController::class, 'lookupVehicleByRegistration'])
            ->middleware('throttle:40,1'); // Rate limit vehicle lookups
    });
    
    // Lead Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])
            ->middleware('permission:dealer.leads.view');
        
        Route::get('show/{id}', [LeadController::class, 'show'])
            ->middleware('permission:dealer.leads.view');
        
        Route::post('assign/{id}', [LeadController::class, 'assign'])
            ->middleware('permission:dealer.leads.assign');
        
        Route::post('stage/{id}', [LeadController::class, 'updateStage'])
            ->middleware('permission:dealer.leads.update');
        
        Route::post('intent/{id}', [LeadController::class, 'updateIntent'])
            ->middleware('permission:dealer.leads.update');
        
        Route::post('category/{id}', [LeadController::class, 'updateCategory'])
            ->middleware('permission:dealer.leads.update');
        
        Route::get('messages/{id}', [LeadController::class, 'getMessages'])
            ->middleware('permission:dealer.leads.messages');
        
        Route::post('messages/{id}', [LeadController::class, 'sendMessage'])
            ->middleware('permission:dealer.leads.messages');
    });

    // CRM extensions (notes, tasks, activities, reminders)
    Route::prefix('leads/crm')->group(function () {
        Route::get('lost-reasons', [LeadCrmController::class, 'lostReasons'])
            ->middleware('permission:dealer.leads.view');
        Route::get('{leadId}/activities', [LeadCrmController::class, 'activities'])
            ->middleware('permission:dealer.leads.view');
        Route::get('{leadId}/notes', [LeadCrmController::class, 'notes'])
            ->middleware('permission:dealer.leads.view');
        Route::post('{leadId}/notes', [LeadCrmController::class, 'storeNote'])
            ->middleware('permission:dealer.crm.notes');
        Route::get('{leadId}/tasks', [LeadCrmController::class, 'tasks'])
            ->middleware('permission:dealer.leads.view');
        Route::post('{leadId}/tasks', [LeadCrmController::class, 'storeTask'])
            ->middleware('permission:dealer.crm.tasks');
        Route::put('{leadId}/tasks/{taskId}', [LeadCrmController::class, 'updateTask'])
            ->middleware('permission:dealer.crm.tasks');
        Route::post('{leadId}/reminders', [LeadCrmController::class, 'storeReminder'])
            ->middleware('permission:dealer.crm.tasks');
    });

    // Saved searches (dealer/staff panel)
    Route::prefix('saved-searches')->group(function () {
        Route::get('/', [SavedSearchController::class, 'index'])
            ->middleware('permission:dealer.saved-searches.view');
        Route::post('/', [SavedSearchController::class, 'store'])
            ->middleware('permission:dealer.saved-searches.manage');
        Route::delete('/{id}', [SavedSearchController::class, 'destroy'])
            ->middleware('permission:dealer.saved-searches.manage');
    });
    
    // Enquiry Management
    Route::prefix('enquiries')->group(function () {
        Route::get('/', [DealerEnquiryController::class, 'index'])
            ->middleware('permission:dealer.enquiries.view');

        Route::get('show/{id}', [DealerEnquiryController::class, 'show'])
            ->middleware('permission:dealer.enquiries.view');

        Route::post('status/{id}', [DealerEnquiryController::class, 'updateStatus'])
            ->middleware('permission:dealer.enquiries.update');

        Route::post('type/{id}', [DealerEnquiryController::class, 'updateType'])
            ->middleware('permission:dealer.enquiries.update');
    });
    
    // Dealer Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [DealerProfileController::class, 'show']);
        Route::post('/update', [DealerProfileController::class, 'update']);
    });
    
    // Dealer Staff
    Route::prefix('staff')->group(function () {
        Route::get('/', [DealerStaffController::class, 'index'])
            ->middleware('permission:dealer.staff.manage');
        Route::post('/', [DealerStaffController::class, 'store'])
            ->middleware('permission:dealer.staff.manage');
        Route::put('/{userId}', [DealerStaffController::class, 'update'])
            ->middleware('permission:dealer.staff.manage');
        Route::delete('/{userId}', [DealerStaffController::class, 'destroy'])
            ->middleware('permission:dealer.staff.manage');
    });
    
    // Subscriptions (admin only)
    Route::prefix('subscription')->group(function () {
        Route::get('/change-request', [SubscriptionController::class, 'getPendingChangeRequest'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::post('/change-request/cancel', [SubscriptionController::class, 'cancelChangeRequest'])
            ->middleware('permission:dealer.subscription.manage');
        Route::get('/', [SubscriptionController::class, 'show'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::get('/features', [SubscriptionController::class, 'getFeatures'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::get('/history', [SubscriptionController::class, 'getHistory'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::get('/usage', [SubscriptionController::class, 'getUsage'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::post('/', [SubscriptionController::class, 'store'])
            ->middleware('permission:dealer.subscription.manage');
    });

    Route::prefix('onboarding')->group(function () {
        Route::get('/', [DealerOnboardingController::class, 'status']);
        Route::post('/advance', [DealerOnboardingController::class, 'advance']);
    });

    Route::prefix('billing')->group(function () {
        Route::get('/config', [DealerBillingController::class, 'config'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::get('/invoices', [DealerBillingController::class, 'invoices'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::get('/invoices/{id}', [DealerBillingController::class, 'showInvoice'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
        Route::post('/invoices/{id}/checkout', [DealerBillingController::class, 'checkoutInvoice'])
            ->middleware('permission:dealer.subscription.manage');
        Route::post('/subscription-checkout', [DealerBillingController::class, 'checkoutSubscription'])
            ->middleware('permission:dealer.subscription.manage');
        Route::get('/payments', [DealerBillingController::class, 'paymentHistory'])
            ->middleware('permission:dealer.subscription.manage,staff.subscription.view');
    });
    
    // Available Plans
    Route::get('/plans', [SubscriptionController::class, 'getAvailablePlans'])
        ->middleware('permission:dealer.subscription.manage');
    
    // Audit Logs (admin only)
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [DealerAuditLogController::class, 'index'])
            ->middleware('permission:dealer.audit.view');
        Route::get('/{id}', [DealerAuditLogController::class, 'show'])
            ->middleware('permission:dealer.audit.view');
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [DealerAnalyticsController::class, 'overview'])
            ->middleware('dealer.feature:analytics');
        Route::get('/leads', [DealerAnalyticsController::class, 'leads'])
            ->middleware('dealer.feature:analytics');
        Route::get('/vehicles', [DealerAnalyticsController::class, 'vehicles'])
            ->middleware('dealer.feature:analytics');
        Route::get('/marketing', [DealerAnalyticsController::class, 'marketing'])
            ->middleware('dealer.feature:analytics');
        Route::get('/subscription', [DealerAnalyticsController::class, 'subscription']);
        Route::get('/funnel', [DealerAnalyticsController::class, 'funnel'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:analytics_listing_funnel']);
        Route::get('/stock', [DealerAnalyticsController::class, 'stock'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:advanced_analytics']);
        Route::get('/assignees', [DealerAnalyticsController::class, 'assignees'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:advanced_analytics']);
        Route::get('/trends', [DealerAnalyticsController::class, 'trends'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:advanced_analytics']);
        Route::get('/channels', [DealerAnalyticsController::class, 'channels'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:advanced_analytics']);
        Route::get('/export', [DealerAnalyticsController::class, 'export'])
            ->middleware('dealer.feature:analytics');
        Route::get('/export-pdf', [DealerAnalyticsController::class, 'exportPdf'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:analytics_pdf_export']);
        Route::get('/market-pulse', [DealerAnalyticsController::class, 'marketPulse'])
            ->middleware(['dealer.feature:analytics', 'dealer.feature:analytics_dealer_benchmark']);
    });

    Route::prefix('ai')->group(function () {
        Route::get('/config', [DealerAiController::class, 'config'])
            ->middleware('permission:dealer.ai.use,staff.ai.use');
        Route::post('/generate', [DealerAiController::class, 'generate'])
            ->middleware(['permission:dealer.ai.use,staff.ai.use', 'throttle:10,1']);
    });
});
