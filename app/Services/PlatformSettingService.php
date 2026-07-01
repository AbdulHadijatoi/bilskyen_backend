<?php

namespace App\Services;

use App\Models\IntegrationLog;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class PlatformSettingService
{
    private const CACHE_PREFIX = 'platform_setting:';

    private const SECRET_KEY_SUFFIXES = ['_key', '_secret', '_token', '_password'];

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group, bool $decryptSecrets = true): array
    {
        $settings = PlatformSetting::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $value = $this->decodeValue($setting, $decryptSecrets);
            $result[$setting->key] = $value;
        }

        return $result;
    }

    public function get(string $group, string $key, mixed $default = null, bool $decrypt = true): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$group.'.'.$key;

        return Cache::remember($cacheKey, 300, function () use ($group, $key, $default, $decrypt) {
            $setting = PlatformSetting::where('group', $group)->where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return $this->decodeValue($setting, $decrypt);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setGroup(string $group, array $data, ?int $userId = null): void
    {
        foreach ($data as $key => $value) {
            $this->set($group, (string) $key, $value, $userId);
        }
    }

    public function set(string $group, string $key, mixed $value, ?int $userId = null): void
    {
        $shouldEncrypt = $this->shouldEncryptKey($key) || is_string($value) && str_contains($key, 'secret');

        $stored = is_array($value) ? json_encode($value) : (string) $value;
        if ($shouldEncrypt && $stored !== '' && $stored !== '********') {
            $stored = Crypt::encryptString($stored);
        }

        PlatformSetting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'is_encrypted' => $shouldEncrypt]
        );

        Cache::forget(self::CACHE_PREFIX.$group.'.'.$key);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublicGroup(string $group): array
    {
        $all = $this->getGroup($group, true);

        return collect($all)->map(function ($value, $key) {
            if ($this->shouldEncryptKey((string) $key) && is_string($value) && $value !== '') {
                return '********';
            }

            return $value;
        })->all();
    }

    public function logIntegration(
        string $provider,
        string $action,
        string $status,
        ?string $message = null,
        ?int $userId = null,
        ?array $meta = null
    ): IntegrationLog {
        return IntegrationLog::create([
            'provider' => $provider,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'user_id' => $userId,
            'meta' => $meta,
        ]);
    }

    public function clearCache(): void
    {
        $settings = PlatformSetting::select('group', 'key')->get();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX.$setting->group.'.'.$setting->key);
        }
    }

    private function decodeValue(PlatformSetting $setting, bool $decrypt): mixed
    {
        $raw = $setting->value;
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($setting->is_encrypted && $decrypt) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable) {
                return null;
            }
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if ($raw === 'true') {
            return true;
        }
        if ($raw === 'false') {
            return false;
        }

        return $raw;
    }

    private function shouldEncryptKey(string $key): bool
    {
        if (in_array($key, ['secret_key', 'webhook_secret', 'api_key'], true)) {
            return true;
        }

        if (str_ends_with($key, '_api_key')) {
            return true;
        }

        foreach (['_secret', '_token', '_password'] as $suffix) {
            if (str_ends_with($key, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
