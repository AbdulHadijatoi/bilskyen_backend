<?php

namespace App\Console\Commands;

use App\Services\CmsPhase3CopyService;
use Illuminate\Console\Command;

class ApplyPhase3CmsCopyCommand extends Command
{
    protected $signature = 'cms:apply-phase3-copy';

    protected $description = 'Apply Phase 3 blog/guide copy for existing CMS slugs only';

    public function handle(CmsPhase3CopyService $copyService): int
    {
        $result = $copyService->apply();

        $this->info($result['blog'] ? 'Updated blog copy.' : 'Blog slug not found; skipped.');
        if ($result['guides'] === []) {
            $this->info('No matching guide rows; skipped.');
        } else {
            $this->info('Updated guides: '.implode(', ', $result['guides']));
        }

        return self::SUCCESS;
    }
}
