<?php

namespace App\Console\Commands;

use App\Services\Feeds\SftpFeedUploadService;
use Illuminate\Console\Command;

class UploadPlatformFeedSftp extends Command
{
    protected $signature = 'feeds:upload-sftp';

    protected $description = 'Upload platform vehicle feed to configured SFTP destination';

    public function handle(SftpFeedUploadService $service): int
    {
        $result = $service->uploadPlatformFeed();
        $result['success'] ? $this->info($result['message']) : $this->error($result['message']);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
