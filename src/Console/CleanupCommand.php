<?php

namespace Walsgit\RecycleBin\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Input\InputOption;

class CleanupCommand extends AbstractCommand
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('recycle-bin:cleanup')
            ->setDescription('Permanently delete eligible soft-deleted content.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List eligible content without deleting it')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Run even when automatic cleanup is disabled');
    }

    protected function fire(): int
    {
        $enabled = (bool) $this->settings->get('walsgit-recycle-bin.auto_cleanup_enabled', false);
        if (! $enabled && ! $this->input->getOption('force')) {
            $this->comment('Automatic cleanup is disabled.');

            return 0;
        }

        $days = max(1, (int) $this->settings->get('walsgit-recycle-bin.auto_cleanup_days', 90));
        $cutoff = Carbon::now()->subDays($days);
        $dryRun = (bool) $this->input->getOption('dry-run');
        $discussions = Discussion::query()
            ->whereNotNull('hidden_at')
            ->whereColumn('hidden_user_id', 'user_id')
            ->where('hidden_at', '<=', $cutoff)
            ->get();
        $discussionIds = [];

        foreach ($discussions as $discussion) {
            $hasOtherAuthors = $discussion->posts()
                ->where('user_id', '<>', $discussion->user_id)
                ->where('type', 'comment')
                ->exists();

            if ($hasOtherAuthors) {
                continue;
            }

            $discussionIds[] = (int) $discussion->id;
            if (! $dryRun) {
                $discussion->delete();
            }
        }

        $posts = Post::query()
            ->whereNotNull('hidden_at')
            ->whereColumn('hidden_user_id', 'user_id')
            ->where('hidden_at', '<=', $cutoff)
            ->where('type', 'comment')
            ->whereNotIn('discussion_id', $discussionIds)
            ->get();

        foreach ($posts as $post) {
            if (! $dryRun) {
                $post->delete();
            }
        }

        $action = $dryRun ? 'eligible' : 'deleted';
        $this->info(sprintf('Cleanup %s: %d discussions, %d posts.', $action, count($discussionIds), $posts->count()));

        return 0;
    }
}
