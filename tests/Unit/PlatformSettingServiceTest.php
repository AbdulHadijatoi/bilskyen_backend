<?php

namespace Tests\Unit;

use App\Models\AiPromptTemplate;
use App\Models\PlatformSetting;
use App\Services\AiService;
use App\Services\PlatformSettingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class PlatformSettingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('platform_settings');
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('key');
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('platform_settings');
        parent::tearDown();
    }

    public function test_masked_secret_placeholder_does_not_overwrite_existing_key(): void
    {
        $service = app(PlatformSettingService::class);

        $service->set('ai', 'openai_api_key', 'sk-real-secret-key');

        $storedBefore = PlatformSetting::where('group', 'ai')->where('key', 'openai_api_key')->first();
        $this->assertNotNull($storedBefore);
        $this->assertNotSame('********', $storedBefore->value);

        $service->set('ai', 'openai_api_key', '********');

        $storedAfter = PlatformSetting::where('group', 'ai')->where('key', 'openai_api_key')->first();
        $this->assertSame($storedBefore->value, $storedAfter->value);
        $this->assertSame('sk-real-secret-key', $service->get('ai', 'openai_api_key'));
    }

    public function test_new_secret_is_encrypted_at_rest(): void
    {
        $service = app(PlatformSettingService::class);
        Cache::flush();
        $service->set('ai', 'openai_api_key', 'sk-new-key');

        $row = PlatformSetting::where('group', 'ai')->where('key', 'openai_api_key')->first();
        $this->assertTrue($row->is_encrypted);
        $this->assertNotSame('sk-new-key', $row->value);
        $this->assertSame('sk-new-key', Crypt::decryptString($row->value));
    }
}
