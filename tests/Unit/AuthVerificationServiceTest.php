<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthVerificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\SetsUpMinimalAuthSchema;
use Tests\TestCase;

class AuthVerificationServiceTest extends TestCase
{
    use SetsUpMinimalAuthSchema;

    private AuthVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMinimalAuthSchema();
        $this->service = $this->app->make(AuthVerificationService::class);
    }

    public function test_verify_email_by_user_id_and_hash_sets_email_verified_at(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Str::random(64);

        DB::table('verifications')->insert([
            'identifier' => 'email_verify:' . $user->email,
            'value' => Hash::make($token),
            'expires_at' => Carbon::now()->addHours(24),
            'created_at' => Carbon::now(),
        ]);

        $verified = $this->service->verifyEmailByUserIdAndHash($user->id, $token);

        $this->assertNotNull($verified);
        $this->assertNotNull($verified->email_verified_at);
    }

    public function test_verify_email_by_token_sets_email_verified_at(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Str::random(64);

        DB::table('verifications')->insert([
            'identifier' => 'email_verify:' . $user->email,
            'value' => Hash::make($token),
            'expires_at' => Carbon::now()->addHours(24),
            'created_at' => Carbon::now(),
        ]);

        $verified = $this->service->verifyEmailByToken($token, $user->id);

        $this->assertNotNull($verified);
        $this->assertNotNull($verified->email_verified_at);
    }

    public function test_verify_magic_link_token_sets_email_verified_at(): void
    {
        $user = User::factory()->unverified()->create();
        $token = Str::random(64);

        DB::table('verifications')->insert([
            'identifier' => 'magic_link:' . $user->email,
            'value' => Hash::make($token),
            'expires_at' => Carbon::now()->addMinutes(15),
            'created_at' => Carbon::now(),
        ]);

        $result = $this->service->verifyMagicLinkToken($token);

        $this->assertNotNull($result);
        $this->assertNotNull($result['user']->email_verified_at);
    }

    public function test_change_email_updates_email_and_resets_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => 'Password123!',
        ]);

        $result = $this->service->changeEmail($user, 'new@example.com', 'Password123!');

        $this->assertTrue($result['success']);
        $this->assertSame('new@example.com', $result['user']->email);
        $this->assertNull($result['user']->email_verified_at);
    }

    public function test_change_email_rejects_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $result = $this->service->changeEmail($user, 'new@example.com', 'WrongPassword!');

        $this->assertFalse($result['success']);
        $this->assertSame(401, $result['status']);
    }
}
