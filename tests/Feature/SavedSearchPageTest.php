<?php

namespace Tests\Feature;

use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AuthenticateWeb;
use App\Http\Middleware\SeoRedirectMiddleware;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SavedSearchPageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unauthenticated_list_redirects_to_login(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/gemte-soegninger', 'GET'));
        $this->assertSame('saved-searches.index', $route->getName());
        $this->assertTrue(in_array('auth.web', $route->gatherMiddleware(), true));

        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAuthenticatedUser')->andReturn(null);

        $request = Request::create('/gemte-soegninger', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        $response = (new AuthenticateWeb($auth))->handle($request, fn () => new Response());

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/auth/login', $response->headers->get('Location'));
        $this->assertStringContainsString('return_url='.rawurlencode('/gemte-soegninger'), $response->headers->get('Location'));
    }

    public function test_apply_url_contains_stored_filter_keys(): void
    {
        $query = SavedSearch::toVehiclesQuery([
            'brand_id' => [4, 9],
            'price_to' => 200000,
            'fuel_type_id' => [3],
        ]);

        $this->assertStringContainsString('brand_id%5B%5D=4', $query);
        $this->assertStringContainsString('brand_id%5B%5D=9', $query);
        $this->assertStringContainsString('price_to=200000', $query);
        $this->assertStringContainsString('fuel_type_id%5B%5D=3', $query);
    }

    public function test_owner_lists_only_own_saved_searches(): void
    {
        $this->createSavedSearchesTable();
        $user = $this->user(7);
        $this->mockAuth($user);

        SavedSearch::create([
            'user_id' => 7,
            'name' => 'Elbiler',
            'filters' => ['brand_id' => [4], 'price_to' => 200000],
            'created_at' => now(),
        ]);
        SavedSearch::create([
            'user_id' => 8,
            'name' => 'Someone else',
            'filters' => ['price_to' => 1],
            'created_at' => now(),
        ]);

        $view = $this->app->make(HomeController::class)->showSavedSearches(Request::create('/gemte-soegninger', 'GET'));

        $this->assertSame('saved-searches', $view->name());
        $searches = $view->getData()['searches'];
        $this->assertCount(1, $searches);
        $this->assertSame('Elbiler', $searches->first()->name);
        $this->assertStringContainsString('brand_id', SavedSearch::toVehiclesQuery($searches->first()->filters));
    }

    public function test_owner_can_delete_only_own_saved_searches(): void
    {
        $this->createSavedSearchesTable();
        $user = $this->user(7);
        $this->mockAuth($user);

        $mine = SavedSearch::create([
            'user_id' => 7,
            'name' => 'Mine',
            'filters' => ['price_to' => 100000],
            'created_at' => now(),
        ]);
        $theirs = SavedSearch::create([
            'user_id' => 8,
            'name' => 'Theirs',
            'filters' => ['price_to' => 1],
            'created_at' => now(),
        ]);

        $controller = $this->app->make(AiSearchController::class);
        $request = Request::create('/gemte-soegninger/'.$theirs->id, 'DELETE');

        $this->assertSame(404, $controller->destroyWeb((int) $theirs->id, $request)->getStatusCode());
        $this->assertTrue(SavedSearch::query()->whereKey($theirs->id)->exists());

        $this->assertSame(200, $controller->destroyWeb((int) $mine->id, $request)->getStatusCode());
        $this->assertFalse(SavedSearch::query()->whereKey($mine->id)->exists());
    }

    public function test_guest_http_list_redirects_login(): void
    {
        $this->withoutMiddleware(SeoRedirectMiddleware::class);
        $this->mockAuth(null);

        $response = $this->get('/gemte-soegninger');

        $response->assertRedirect();
        $this->assertStringContainsString('/auth/login', $response->headers->get('Location'));
        $this->assertStringContainsString('gemte-soegninger', $response->headers->get('Location'));
    }

    private function mockAuth(?User $user): void
    {
        $this->mock(AuthService::class, function ($mock) use ($user) {
            $mock->shouldReceive('getAuthenticatedUser')->andReturn($user);
        });
    }

    private function user(int $id): User
    {
        $user = new User(['name' => 'Buyer']);
        $user->id = $id;
        $user->banned = false;

        return $user;
    }

    private function createSavedSearchesTable(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->json('filters');
            $table->timestamp('created_at')->nullable();
        });
    }
}
