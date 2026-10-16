<?php

/*
 * This file is part of walsgit/recycle-bin.
 *
 * Copyright (c) 2024 Wa!id.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Walsgit\RecycleBin;

use Flarum\Extend;
use Walsgit\RecycleBin\Api\Controller\DiscussionStatisticsController;
use Walsgit\RecycleBin\Api\Controller\PostStatisticsController;
use Walsgit\RecycleBin\Api\Controller\MassDiscussionsController;
use Walsgit\RecycleBin\Api\Controller\MassPostsController;
use Walsgit\RecycleBin\Api\Controller\PermanentDeletePostController;
use Walsgit\RecycleBin\Api\Controller\PermanentDeleteDiscussionController;
use Walsgit\RecycleBin\Api\Controller\PermissionController;
use Walsgit\RecycleBin\Filter\HiddenPostFilter;
use Walsgit\RecycleBin\Filter\ContentPostFilter;
use Walsgit\RecycleBin\Console\CleanupCommand;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),
    new Extend\Locales(__DIR__.'/locale'),
    (new Extend\Console())
        ->command(CleanupCommand::class)
        ->schedule(CleanupCommand::class, function ($event) {
            $event->dailyAt('03:30')->withoutOverlapping();
        }),
    (new Extend\Settings())
        ->default('walsgit-recycle-bin.auto_cleanup_enabled', false)
        ->default('walsgit-recycle-bin.auto_cleanup_days', 90),
    (new Extend\Routes('api'))
        ->get('/recycle-bin/permissions', 'recycle-bin.permissions', PermissionController::class)
        ->get('/recycle-bin/discussion-statistics', 'recycle-bin.discussion-statistics', DiscussionStatisticsController::class)
        ->get('/recycle-bin/post-statistics', 'recycle-bin.post-statistics', PostStatisticsController::class)
        ->post('/recycle-bin/mass-discussions', 'recycle-bin.mass-discussions', MassDiscussionsController::class)
        ->post('/recycle-bin/mass-posts', 'recycle-bin.mass-posts', MassPostsController::class)
        ->delete('/recycle-bin/posts/{id}/permanent', 'recycle-bin.posts.permanent', PermanentDeletePostController::class)
        ->delete('/recycle-bin/discussions/{id}/permanent', 'recycle-bin.discussions.permanent', PermanentDeleteDiscussionController::class),
    (new Extend\SearchDriver(\Flarum\Search\Database\DatabaseSearchDriver::class))
        ->addFilter(\Flarum\Post\Filter\PostSearcher::class, HiddenPostFilter::class)
        ->addFilter(\Flarum\Post\Filter\PostSearcher::class, ContentPostFilter::class),
];
