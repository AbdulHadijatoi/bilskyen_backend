<?php

namespace Tests\Feature;

use App\Constants\VehicleListStatus;
use App\Http\Controllers\HomeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleComparePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_compare_page_omits_unpublished_ids(): void
    {
        $published = $this->insertVehicle(['list_status_id' => VehicleListStatus::PUBLISHED]);
        $draft = $this->insertVehicle(['list_status_id' => VehicleListStatus::DRAFT]);

        $view = $this->app->make(HomeController::class)->showCompare(
            Request::create('/biler/sammenlign', 'GET', [
                'ids' => $published.','.$draft,
            ])
        );

        $this->assertSame('vehicle-compare', $view->name());
        $this->assertSame('noindex, follow', $view->getData()['seo']['robots']);
        $this->assertSame([$published], $view->getData()['vehicles']->pluck('id')->all());
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function insertVehicle(array $attrs = []): int
    {
        $now = now();

        return (int) DB::table('vehicles')->insertGetId(array_merge([
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'Compare car',
            'price' => 100000,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attrs));
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('listing_views_log');
        Schema::dropIfExists('vehicle_images');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('sales_types');
        Schema::dropIfExists('gear_types');
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('dmr_variants');
        Schema::dropIfExists('dmr_models');
        Schema::dropIfExists('dmr_brands');
        Schema::dropIfExists('dmr_drive_energies');

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('list_status_id');
            $table->unsignedBigInteger('dealer_id')->nullable();
            $table->unsignedBigInteger('sales_type_id')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->unsignedBigInteger('gear_type_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('listing_views_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at');
        });

        Schema::create('sales_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('gear_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('cvr')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
        });

        Schema::create('dmr_drive_energies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
        });
    }
}
