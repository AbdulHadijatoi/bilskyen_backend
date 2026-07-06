<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\AdminFeatureController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\AdminDealerInvoiceController;
use App\Http\Controllers\AdminSubscriptionChangeRequestController;
use App\Http\Controllers\AdminDealerController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminFeaturedVehicleController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\Admin\Constants\AdminConstantsController;
use App\Http\Controllers\Admin\Constants\AdminBrandController;
use App\Http\Controllers\Admin\Constants\AdminModelYearController;
use App\Http\Controllers\Admin\Constants\AdminFuelTypeController;
use App\Http\Controllers\Admin\Constants\AdminGearTypeController;
use App\Http\Controllers\Admin\Constants\AdminListingTypeController;
use App\Http\Controllers\Admin\Constants\AdminBodyTypeController;
use App\Http\Controllers\Admin\Constants\AdminColorController;
use App\Http\Controllers\Admin\Constants\AdminVariantController;
use App\Http\Controllers\Admin\Constants\AdminConditionController;
use App\Http\Controllers\Admin\Constants\AdminSalesTypeController;
use App\Http\Controllers\Admin\Constants\AdminPriceTypeController;
use App\Http\Controllers\Admin\Constants\AdminEuronomController;
use App\Http\Controllers\Admin\Constants\AdminVehicleModelController;
use App\Http\Controllers\Admin\Constants\AdminVehicleUseController;
use App\Http\Controllers\Admin\Constants\AdminVehicleListStatusController;
use App\Http\Controllers\Admin\Constants\AdminEquipmentController;
use App\Http\Controllers\Admin\Constants\AdminEquipmentTypeController;
use App\Http\Controllers\AdminTranslationController;
use App\Http\Controllers\AdminHomePageController;
use App\Http\Controllers\AdminAboutPageController;
use App\Http\Controllers\AdminContactPageController;
use App\Http\Controllers\AdminPrivacyPageController;
use App\Http\Controllers\AdminPricingPageController;
use App\Http\Controllers\AdminTermsPageController;
use App\Http\Controllers\AdminLoginPageController;
use App\Http\Controllers\AdminSeoPageController;
use App\Http\Controllers\AdminCmsPostController;
use App\Http\Controllers\AdminLandingPageController;
use App\Http\Controllers\AdminCmsMediaController;
use App\Http\Controllers\AdminSeoRedirectController;
use App\Http\Controllers\AdminSeoToolsController;
use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\AdminOwnershipTaxRuleController;
use App\Http\Controllers\AdminVehicleSpecDefinitionController;
use App\Http\Controllers\AdminDmrDriveEnergyController;
use App\Http\Controllers\Admin\AdminLeadStageController;
use App\Http\Controllers\AdminIntegrationController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminAiController;
use App\Http\Controllers\AdminSyndicationController;

/*
|--------------------------------------------------------------------------
| Admin API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/admin/* via bootstrap/app.php
| All routes require auth:api and role:admin middleware (standardized)
|
*/

// Admin routes (requires authentication and admin role - standardized to auth:api)
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/show/{id}', [AdminUserController::class, 'show']);
        Route::get('/roles', [AdminUserController::class, 'getRoles']);
        Route::post('/create', [AdminUserController::class, 'create'])
            ->middleware(['idempotency']); // Idempotency for user creation
        Route::post('/update/{id}', [AdminUserController::class, 'update']);
        Route::post('/delete/{id}', [AdminUserController::class, 'delete']); // Soft delete
        Route::post('/update-status/{id}', [AdminUserController::class, 'updateStatus']);
        Route::post('/change-password/{id}', [AdminUserController::class, 'changePassword']);
        Route::post('/ban/{id}', [AdminUserController::class, 'ban']);
        Route::post('/unban/{id}', [AdminUserController::class, 'unban']);
    });
    
    // Admin self password change
    Route::post('/change-password', [AdminUserController::class, 'changeOwnPassword']);
    
    // Vehicle Management (Admin can see all dealer listings)
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [AdminVehicleController::class, 'index']);
        Route::get('/pending-review', [AdminVehicleController::class, 'pendingReview']);
        Route::post('/approve-pending/{id}', [AdminVehicleController::class, 'approvePendingReview']);
        Route::post('/reject-pending/{id}', [AdminVehicleController::class, 'rejectPendingReview']);
        Route::post('/renew-listing/{id}', [AdminVehicleController::class, 'renewListing']);
        Route::post('/listing-lifecycle/{id}', [AdminVehicleController::class, 'updateListingLifecycle']);
        Route::get('/show/{id}', [AdminVehicleController::class, 'show']);
        Route::get('/images/{id}', [AdminVehicleController::class, 'getImages']);
        Route::get('/history/{id}', [AdminVehicleController::class, 'getHistory']);
        Route::post('/update/{id}', [AdminVehicleController::class, 'update']);
        Route::post('/update-status/{id}', [AdminVehicleController::class, 'updateStatus']);
        Route::post('/update-images/{id}', [AdminVehicleController::class, 'updateImages']);
        Route::post('/delete-image/{id}', [AdminVehicleController::class, 'deleteImage']);
        Route::post('/update-equipment/{id}', [AdminVehicleController::class, 'updateEquipment']);
        Route::post('/delete/{id}', [AdminVehicleController::class, 'delete']); // Soft delete
    });
    
    // Plan & Subscription Management
    Route::prefix('plans')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index']);
        Route::get('/{id}', [AdminPlanController::class, 'show']);
        Route::post('/', [AdminPlanController::class, 'store'])
            ->middleware(['idempotency']); // Idempotency for plan creation
        Route::put('/{id}', [AdminPlanController::class, 'update']);
        Route::delete('/{id}', [AdminPlanController::class, 'destroy']); // Soft delete
        Route::get('/{id}/features', [AdminPlanController::class, 'getFeatures']);
        Route::post('/{id}/features', [AdminPlanController::class, 'assignFeature']);
        Route::delete('/{id}/features/{featureId}', [AdminPlanController::class, 'removeFeature']);
        Route::get('/{id}/availability', [AdminPlanController::class, 'getAvailability']);
        Route::post('/{id}/availability', [AdminPlanController::class, 'syncAvailability']);
        Route::get('/{id}/pricing', [AdminPlanController::class, 'getPricing']);
        Route::post('/{id}/pricing', [AdminPlanController::class, 'updatePricing']);
    });
    
    Route::prefix('subscription-change-requests')->group(function () {
        Route::get('/', [AdminSubscriptionChangeRequestController::class, 'index']);
        Route::post('/{id}/approve', [AdminSubscriptionChangeRequestController::class, 'approve']);
        Route::post('/{id}/reject', [AdminSubscriptionChangeRequestController::class, 'reject']);
    });

    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index']);
        Route::get('/{id}', [AdminSubscriptionController::class, 'show']);
        Route::post('/', [AdminSubscriptionController::class, 'store']);
        Route::put('/{id}', [AdminSubscriptionController::class, 'update']);
        Route::put('/{id}/status', [AdminSubscriptionController::class, 'updateStatus']);
        Route::post('/{id}/cancel', [AdminSubscriptionController::class, 'cancel']);
        Route::post('/{id}/renew', [AdminSubscriptionController::class, 'renew']);
        Route::get('/dealer/{dealerId}', [AdminSubscriptionController::class, 'getDealerSubscriptions']);
    });

    Route::prefix('invoices')->group(function () {
        Route::get('/', [AdminDealerInvoiceController::class, 'index']);
        Route::get('/{id}', [AdminDealerInvoiceController::class, 'show']);
        Route::post('/{id}/mark-sent', [AdminDealerInvoiceController::class, 'markSent']);
        Route::post('/{id}/mark-paid', [AdminDealerInvoiceController::class, 'markPaid']);
    });

    // Dealer Management
    Route::prefix('dealers')->group(function () {
        Route::get('/', [AdminDealerController::class, 'index']);
        Route::get('/list', [AdminDealerController::class, 'list']);
        Route::get('/{id}', [AdminDealerController::class, 'show']);
    });

    // Platform integrations & settings
    Route::prefix('integrations')->group(function () {
        Route::get('/', [AdminIntegrationController::class, 'index']);
        Route::put('/', [AdminIntegrationController::class, 'update']);
        Route::get('/logs', [AdminIntegrationController::class, 'logs']);
        Route::post('/test', [AdminIntegrationController::class, 'test']);
    });

    Route::prefix('syndication')->group(function () {
        Route::get('/providers', [AdminSyndicationController::class, 'providers']);
        Route::get('/logs', [AdminSyndicationController::class, 'logs']);
        Route::post('/dealers/{dealerId}/sync', [AdminSyndicationController::class, 'syncDealer']);
        Route::post('/sftp/test', [AdminSyndicationController::class, 'testSftp']);
        Route::post('/sftp/upload', [AdminSyndicationController::class, 'uploadSftp']);
    });

    Route::prefix('marketing')->group(function () {
        Route::get('/queue', [\App\Http\Controllers\AdminMarketingController::class, 'queue']);
        Route::get('/gdpr-requests', [\App\Http\Controllers\AdminMarketingController::class, 'gdprRequests']);
    });

    Route::prefix('payments')->group(function () {
        Route::get('/', [AdminPaymentController::class, 'index']);
    });

    Route::prefix('ai')->group(function () {
        Route::get('/usage', [AdminAiController::class, 'usage'])
            ->middleware('permission:admin.ai.view');
        Route::get('/prompt-templates', [AdminAiController::class, 'promptTemplates'])
            ->middleware('permission:admin.ai.view');
        Route::put('/prompt-templates/{id}', [AdminAiController::class, 'updatePromptTemplate'])
            ->middleware('permission:admin.ai.manage');
        Route::post('/generate', [AdminAiController::class, 'generate'])
            ->middleware(['permission:admin.ai.manage', 'throttle:10,1']);
        Route::post('/test', [AdminAiController::class, 'testProvider'])
            ->middleware('permission:admin.ai.manage');
    });
    
    // Feature Management
    Route::prefix('features')->group(function () {
        Route::get('/', [AdminFeatureController::class, 'index']);
        Route::get('/{id}', [AdminFeatureController::class, 'show']);
        Route::post('/', [AdminFeatureController::class, 'store']);
        Route::put('/{id}', [AdminFeatureController::class, 'update']);
        Route::delete('/{id}', [AdminFeatureController::class, 'destroy']);
    });
    
    // CMS Management
    Route::prefix('pages')->group(function () {
        Route::get('/', [AdminPageController::class, 'index']);
        Route::get('/{id}', [AdminPageController::class, 'show']);
        Route::post('/', [AdminPageController::class, 'store']);
        Route::put('/{id}', [AdminPageController::class, 'update']);
        Route::delete('/{id}', [AdminPageController::class, 'destroy']);
        Route::put('/{id}/publish', [AdminPageController::class, 'publish']);
    });
    
    // Home Page Content Management
    Route::prefix('home-page-content')->group(function () {
        Route::get('/', [AdminHomePageController::class, 'index']);
        Route::post('/bulk-update', [AdminHomePageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminHomePageController::class, 'update']);
    });
    
    // About Page Content Management
    Route::prefix('about-page-content')->group(function () {
        Route::get('/', [AdminAboutPageController::class, 'index']);
        Route::post('/bulk-update', [AdminAboutPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminAboutPageController::class, 'update']);
        Route::post('/images/upload', [AdminAboutPageController::class, 'uploadImage']);
        Route::delete('/images/{imageId}', [AdminAboutPageController::class, 'deleteImage']);
    });
    
    // Contact Page Content Management
    Route::prefix('contact-page-content')->group(function () {
        Route::get('/', [AdminContactPageController::class, 'index']);
        Route::post('/bulk-update', [AdminContactPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminContactPageController::class, 'update']);
        Route::post('/images/upload', [AdminContactPageController::class, 'uploadImage']);
        Route::delete('/images/{imageId}', [AdminContactPageController::class, 'deleteImage']);
    });
    
    // Privacy Page Content Management
    Route::prefix('privacy-page-content')->group(function () {
        Route::get('/', [AdminPrivacyPageController::class, 'index']);
        Route::post('/bulk-update', [AdminPrivacyPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminPrivacyPageController::class, 'update']);
    });
    
    // Terms Page Content Management
    Route::prefix('terms-page-content')->group(function () {
        Route::get('/', [AdminTermsPageController::class, 'index']);
        Route::post('/bulk-update', [AdminTermsPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminTermsPageController::class, 'update']);
    });

    // Dealer pricing page content (FAQ, hero, footnote)
    Route::prefix('pricing-page-content')->group(function () {
        Route::get('/', [AdminPricingPageController::class, 'index']);
        Route::post('/bulk-update', [AdminPricingPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminPricingPageController::class, 'update']);
    });

    // Login Page Content Management (auth layout sidebar testimonial)
    Route::prefix('login-page-content')->group(function () {
        Route::get('/', [AdminLoginPageController::class, 'index']);
        Route::post('/bulk-update', [AdminLoginPageController::class, 'updateBulk']);
        Route::post('/{sectionKey}', [AdminLoginPageController::class, 'update']);
    });

    // SEO Pages Management
    Route::prefix('seo-pages')->group(function () {
        Route::get('/page-key-options', [AdminSeoPageController::class, 'pageKeyOptions']);
        Route::get('/', [AdminSeoPageController::class, 'index']);
        Route::get('/{id}', [AdminSeoPageController::class, 'show']);
        Route::post('/', [AdminSeoPageController::class, 'store']);
        Route::put('/{id}', [AdminSeoPageController::class, 'update']);
        Route::delete('/{id}', [AdminSeoPageController::class, 'destroy']);
    });

    // R5 CMS & SEO tools
    Route::prefix('cms/posts')->group(function () {
        Route::get('/categories', [AdminCmsPostController::class, 'categories']);
        Route::post('/categories', [AdminCmsPostController::class, 'storeCategory']);
        Route::get('/', [AdminCmsPostController::class, 'index']);
        Route::get('/{id}', [AdminCmsPostController::class, 'show']);
        Route::post('/', [AdminCmsPostController::class, 'store']);
        Route::put('/{id}', [AdminCmsPostController::class, 'update']);
        Route::delete('/{id}', [AdminCmsPostController::class, 'destroy']);
        Route::post('/{id}/versions/{versionId}/restore', [AdminCmsPostController::class, 'restoreVersion']);
    });

    Route::prefix('cms/landing-pages')->group(function () {
        Route::get('/', [AdminLandingPageController::class, 'index']);
        Route::get('/{id}', [AdminLandingPageController::class, 'show']);
        Route::post('/', [AdminLandingPageController::class, 'store']);
        Route::put('/{id}', [AdminLandingPageController::class, 'update']);
        Route::delete('/{id}', [AdminLandingPageController::class, 'destroy']);
        Route::post('/{id}/versions/{versionId}/restore', [AdminLandingPageController::class, 'restoreVersion']);
    });

    Route::prefix('cms/media')->group(function () {
        Route::get('/', [AdminCmsMediaController::class, 'index']);
        Route::post('/', [AdminCmsMediaController::class, 'store']);
        Route::put('/{id}', [AdminCmsMediaController::class, 'update']);
        Route::delete('/{id}', [AdminCmsMediaController::class, 'destroy']);
    });

    Route::prefix('seo/redirects')->group(function () {
        Route::get('/', [AdminSeoRedirectController::class, 'index']);
        Route::post('/', [AdminSeoRedirectController::class, 'store']);
        Route::put('/{id}', [AdminSeoRedirectController::class, 'update']);
        Route::delete('/{id}', [AdminSeoRedirectController::class, 'destroy']);
    });

    Route::prefix('seo/tools')->group(function () {
        Route::get('/robots', [AdminSeoToolsController::class, 'robotsShow']);
        Route::put('/robots', [AdminSeoToolsController::class, 'robotsUpdate']);
        Route::get('/cookie-consent', [AdminSeoToolsController::class, 'cookieConsentShow']);
        Route::put('/cookie-consent', [AdminSeoToolsController::class, 'cookieConsentUpdate']);
        Route::get('/audit', [AdminSeoToolsController::class, 'audit']);
        Route::get('/schema/presets', [AdminSeoToolsController::class, 'schemaPresets']);
        Route::post('/schema/build', [AdminSeoToolsController::class, 'buildSchema']);
    });
    
    // Featured Vehicles Management
    Route::prefix('featured-vehicles')->group(function () {
        Route::get('/', [AdminFeaturedVehicleController::class, 'index']);
        Route::post('/create', [AdminFeaturedVehicleController::class, 'create']);
        Route::post('/update/{id}', [AdminFeaturedVehicleController::class, 'update']);
        Route::post('/delete/{id}', [AdminFeaturedVehicleController::class, 'delete']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
        Route::get('/revenue', [AdminAnalyticsController::class, 'revenue']);
        Route::get('/dealers', [AdminAnalyticsController::class, 'dealers']);
        Route::get('/vehicles', [AdminAnalyticsController::class, 'vehicles']);
        Route::get('/leads', [AdminAnalyticsController::class, 'leads']);
        Route::get('/activity', [AdminAnalyticsController::class, 'activity']);
        Route::get('/funnel', [AdminAnalyticsController::class, 'funnel']);
        Route::get('/cohort', [AdminAnalyticsController::class, 'cohort']);
        Route::get('/integrations', [AdminAnalyticsController::class, 'integrations']);
        Route::get('/trends', [AdminAnalyticsController::class, 'trends']);
        Route::get('/export', [AdminAnalyticsController::class, 'export']);
    });
    
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AdminAuditLogController::class, 'index']);
        Route::get('/{id}', [AdminAuditLogController::class, 'show']);
    });
    
    // Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'getNotifications']);
    
    // Permission Management (keep existing)
    Route::prefix('permissions')->group(function () {
        Route::post('/get-all-items', [PermissionManagementController::class, 'getAllItems']);
        Route::post('/get-models', [PermissionManagementController::class, 'getModels']);
        Route::post('/model-items', [PermissionManagementController::class, 'modelItems']);
        Route::post('/assign', [PermissionManagementController::class, 'assign']);
        Route::post('/revoke', [PermissionManagementController::class, 'revoke']);
        Route::post('/clear-cache', [PermissionManagementController::class, 'clearCache']);
    });
    
    // Constants Data
    Route::get('/constants', [AdminConstantsController::class, 'getConstantsData']);
    
    // Constants Management
    Route::prefix('brands')->group(function () {
        Route::get('/', [AdminBrandController::class, 'index']);
        Route::get('show/{id}', [AdminBrandController::class, 'show']);
        Route::post('/create', [AdminBrandController::class, 'create']);
        Route::post('/update/{id}', [AdminBrandController::class, 'update']);
        Route::post('delete/{id}', [AdminBrandController::class, 'delete']);
    });
    
    Route::prefix('model-years')->group(function () {
        Route::get('/', [AdminModelYearController::class, 'index']);
        Route::get('show/{id}', [AdminModelYearController::class, 'show']);
        Route::post('/create', [AdminModelYearController::class, 'create']);
        Route::post('/update/{id}', [AdminModelYearController::class, 'update']);
        Route::post('delete/{id}', [AdminModelYearController::class, 'delete']);
    });
    
    Route::prefix('fuel-types')->group(function () {
        Route::get('/', [AdminFuelTypeController::class, 'index']);
        Route::get('show/{id}', [AdminFuelTypeController::class, 'show']);
        Route::post('/create', [AdminFuelTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminFuelTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminFuelTypeController::class, 'delete']);
    });
    
    Route::prefix('gear-types')->group(function () {
        Route::get('/', [AdminGearTypeController::class, 'index']);
        Route::get('show/{id}', [AdminGearTypeController::class, 'show']);
        Route::post('/create', [AdminGearTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminGearTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminGearTypeController::class, 'delete']);
    });
    
    Route::prefix('listing-types')->group(function () {
        Route::get('/', [AdminListingTypeController::class, 'index']);
        Route::get('show/{id}', [AdminListingTypeController::class, 'show']);
        Route::post('/create', [AdminListingTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminListingTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminListingTypeController::class, 'delete']);
    });
    
    Route::prefix('body-types')->group(function () {
        Route::get('/', [AdminBodyTypeController::class, 'index']);
        Route::get('show/{id}', [AdminBodyTypeController::class, 'show']);
        Route::post('/create', [AdminBodyTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminBodyTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminBodyTypeController::class, 'delete']);
    });
    
    Route::prefix('colors')->group(function () {
        Route::get('/', [AdminColorController::class, 'index']);
        Route::get('show/{id}', [AdminColorController::class, 'show']);
        Route::post('/create', [AdminColorController::class, 'create']);
        Route::post('/update/{id}', [AdminColorController::class, 'update']);
        Route::post('delete/{id}', [AdminColorController::class, 'delete']);
    });
    
    Route::prefix('variants')->group(function () {
        Route::get('/', [AdminVariantController::class, 'index']);
        Route::get('show/{id}', [AdminVariantController::class, 'show']);
        Route::post('/create', [AdminVariantController::class, 'create']);
        Route::post('/update/{id}', [AdminVariantController::class, 'update']);
        Route::post('delete/{id}', [AdminVariantController::class, 'delete']);
    });
    
    Route::prefix('conditions')->group(function () {
        Route::get('/', [AdminConditionController::class, 'index']);
        Route::get('show/{id}', [AdminConditionController::class, 'show']);
        Route::post('/create', [AdminConditionController::class, 'create']);
        Route::post('/update/{id}', [AdminConditionController::class, 'update']);
        Route::post('delete/{id}', [AdminConditionController::class, 'delete']);
    });
    
    Route::prefix('sales-types')->group(function () {
        Route::get('/', [AdminSalesTypeController::class, 'index']);
        Route::get('show/{id}', [AdminSalesTypeController::class, 'show']);
        Route::post('/create', [AdminSalesTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminSalesTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminSalesTypeController::class, 'delete']);
    });
    
    Route::prefix('price-types')->group(function () {
        Route::get('/', [AdminPriceTypeController::class, 'index']);
        Route::get('show/{id}', [AdminPriceTypeController::class, 'show']);
        Route::post('/create', [AdminPriceTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminPriceTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminPriceTypeController::class, 'delete']);
    });
    
    Route::prefix('euronorms')->group(function () {
        Route::get('/', [AdminEuronomController::class, 'index']);
        Route::get('show/{id}', [AdminEuronomController::class, 'show']);
        Route::post('/create', [AdminEuronomController::class, 'create']);
        Route::post('/update/{id}', [AdminEuronomController::class, 'update']);
        Route::post('delete/{id}', [AdminEuronomController::class, 'delete']);
    });
    
    Route::prefix('vehicle-models')->group(function () {
        Route::get('/', [AdminVehicleModelController::class, 'index']);
        Route::get('for-listing-filters', [AdminVehicleModelController::class, 'indexForListingFilters']);
        Route::get('show/{id}', [AdminVehicleModelController::class, 'show']);
        Route::post('/create', [AdminVehicleModelController::class, 'create']);
        Route::post('/update/{id}', [AdminVehicleModelController::class, 'update']);
        Route::post('delete/{id}', [AdminVehicleModelController::class, 'delete']);
    });
    
    Route::prefix('vehicle-uses')->group(function () {
        Route::get('/', [AdminVehicleUseController::class, 'index']);
        Route::get('show/{id}', [AdminVehicleUseController::class, 'show']);
        Route::post('/create', [AdminVehicleUseController::class, 'create']);
        Route::post('/update/{id}', [AdminVehicleUseController::class, 'update']);
        Route::post('delete/{id}', [AdminVehicleUseController::class, 'delete']);
    });
    
    Route::prefix('vehicle-list-statuses')->group(function () {
        Route::get('/', [AdminVehicleListStatusController::class, 'index']);
        Route::get('show/{id}', [AdminVehicleListStatusController::class, 'show']);
        Route::post('/create', [AdminVehicleListStatusController::class, 'create']);
        Route::post('/update/{id}', [AdminVehicleListStatusController::class, 'update']);
        Route::post('delete/{id}', [AdminVehicleListStatusController::class, 'delete']);
    });
    
    Route::prefix('equipment-types')->group(function () {
        Route::get('/', [AdminEquipmentTypeController::class, 'index']);
        Route::get('show/{id}', [AdminEquipmentTypeController::class, 'show']);
        Route::post('/create', [AdminEquipmentTypeController::class, 'create']);
        Route::post('/update/{id}', [AdminEquipmentTypeController::class, 'update']);
        Route::post('delete/{id}', [AdminEquipmentTypeController::class, 'delete']);
    });
    
    Route::prefix('equipments')->group(function () {
        Route::get('/', [AdminEquipmentController::class, 'index']);
        Route::get('show/{id}', [AdminEquipmentController::class, 'show']);
        Route::post('/create', [AdminEquipmentController::class, 'create']);
        Route::post('/update/{id}', [AdminEquipmentController::class, 'update']);
        Route::post('delete/{id}', [AdminEquipmentController::class, 'delete']);
    });
    
    // Translation Management
    Route::prefix('translations')->group(function () {
        Route::get('/', [AdminTranslationController::class, 'index']);
        Route::get('/{id}', [AdminTranslationController::class, 'show']);
        Route::post('/', [AdminTranslationController::class, 'store']);
        Route::put('/{id}', [AdminTranslationController::class, 'update']);
        Route::put('/{id}/values/{locale}', [AdminTranslationController::class, 'updateValue']);
        Route::delete('/{id}', [AdminTranslationController::class, 'destroy']);
        Route::post('/import', [AdminTranslationController::class, 'import']);
        Route::get('/export', [AdminTranslationController::class, 'export']);
        Route::get('/locales', [AdminTranslationController::class, 'locales']);
    });

    // Lead stages (fixed IDs, editable Danish names)
    Route::prefix('lead-stages')->group(function () {
        Route::get('/', [AdminLeadStageController::class, 'index']);
        Route::post('/update/{id}', [AdminLeadStageController::class, 'update']);
    });

    // Locations (city / postcode autocomplete dataset)
    Route::prefix('locations')->group(function () {
        Route::get('/', [AdminLocationController::class, 'index']);
        Route::post('/create', [AdminLocationController::class, 'create']);
        Route::post('/update/{id}', [AdminLocationController::class, 'update']);
        Route::post('/delete/{id}', [AdminLocationController::class, 'delete']);
    });

    // Ownership Tax Rules
    Route::prefix('ownership-tax-rules')->group(function () {
        Route::get('/', [AdminOwnershipTaxRuleController::class, 'index']);
        Route::post('/create', [AdminOwnershipTaxRuleController::class, 'create']);
        Route::post('/update/{id}', [AdminOwnershipTaxRuleController::class, 'update']);
        Route::post('/delete/{id}', [AdminOwnershipTaxRuleController::class, 'delete']);
    });

    Route::prefix('vehicle-spec-definitions')->group(function () {
        Route::get('/', [AdminVehicleSpecDefinitionController::class, 'index']);
        Route::get('/show/{id}', [AdminVehicleSpecDefinitionController::class, 'show']);
        Route::post('/create', [AdminVehicleSpecDefinitionController::class, 'create']);
        Route::post('/update/{id}', [AdminVehicleSpecDefinitionController::class, 'update']);
        Route::post('/delete/{id}', [AdminVehicleSpecDefinitionController::class, 'delete']);
    });

    // DMR Drive Energies (fuel type options for ownership tax rules)
    Route::get('/dmr-drive-energies', [AdminDmrDriveEnergyController::class, 'index']);
});
