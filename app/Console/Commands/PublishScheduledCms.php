<?php

namespace App\Console\Commands;

use App\Constants\CmsPostStatus;
use App\Models\CmsPost;
use App\Models\LandingPage;
use Illuminate\Console\Command;

class PublishScheduledCms extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publish scheduled blog posts and landing pages';

    public function handle(): int
    {
        $now = now();

        $posts = CmsPost::where('status', CmsPostStatus::SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->update([
                'status' => CmsPostStatus::PUBLISHED,
                'published_at' => $now,
            ]);

        $pages = LandingPage::where('status', CmsPostStatus::SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->update([
                'status' => CmsPostStatus::PUBLISHED,
                'published_at' => $now,
            ]);

        $this->info("Published {$posts} posts and {$pages} landing pages.");

        return self::SUCCESS;
    }
}
