<?php

namespace App\Services\Feeds;

use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class SftpFeedUploadService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
        private VehicleFeedBuilderService $feedBuilder,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function uploadPlatformFeed(): array
    {
        if (! filter_var($this->platformSettingService->get('syndication', 'sftp_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return ['success' => false, 'message' => __('messages.syndication.sftp_upload_disabled')];
        }

        $host = (string) $this->platformSettingService->get('syndication', 'sftp_host', '');
        $username = (string) $this->platformSettingService->get('syndication', 'sftp_username', '');
        $password = (string) $this->platformSettingService->get('syndication', 'sftp_password', '');

        if ($host === '' || $username === '') {
            return ['success' => false, 'message' => __('messages.syndication.sftp_host_username_required')];
        }

        $format = $this->platformSettingService->get('syndication', 'platform_feed_format', 'xml');
        $dealers = \App\Models\Dealer::has('vehicles')->get();
        $combined = $format === 'json'
            ? json_encode(['generated_at' => now()->toIso8601String(), 'dealers' => $dealers->count()], JSON_PRETTY_PRINT)
            : '<?xml version="1.0"?><platform_feed generated_at="'.now()->toIso8601String().'"/>';

        $localPath = storage_path('app/feeds/platform-feed.'.($format === 'json' ? 'json' : 'xml'));
        if (! is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0755, true);
        }
        file_put_contents($localPath, $combined);

        try {
            $disk = Storage::build([
                'driver' => 'sftp',
                'host' => $host,
                'port' => (int) $this->platformSettingService->get('syndication', 'sftp_port', 22),
                'username' => $username,
                'password' => $password,
                'root' => (string) $this->platformSettingService->get('syndication', 'sftp_remote_path', '/feeds'),
            ]);

            $remoteName = 'platform-feed.'.($format === 'json' ? 'json' : 'xml');
            $disk->put($remoteName, file_get_contents($localPath));

            return ['success' => true, 'message' => __('messages.syndication.sftp_upload_completed', ['filename' => $remoteName])];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! filter_var($this->platformSettingService->get('syndication', 'sftp_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return ['success' => false, 'message' => __('messages.syndication.sftp_disabled')];
        }

        try {
            $disk = Storage::build([
                'driver' => 'sftp',
                'host' => (string) $this->platformSettingService->get('syndication', 'sftp_host', ''),
                'port' => (int) $this->platformSettingService->get('syndication', 'sftp_port', 22),
                'username' => (string) $this->platformSettingService->get('syndication', 'sftp_username', ''),
                'password' => (string) $this->platformSettingService->get('syndication', 'sftp_password', ''),
                'root' => (string) $this->platformSettingService->get('syndication', 'sftp_remote_path', '/feeds'),
            ]);
            $disk->directories('/');

            return ['success' => true, 'message' => __('messages.syndication.sftp_connection_successful')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
