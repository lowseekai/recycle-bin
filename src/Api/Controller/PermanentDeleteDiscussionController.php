<?php

namespace Walsgit\RecycleBin\Api\Controller;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Http\RequestUtil;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermanentDeleteDiscussionController implements RequestHandlerInterface
{
    public function __construct(
        protected DiscussionRepository $discussions,
        protected ConnectionInterface $db
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $id = (int) ($request->getAttribute('id') ?? 0);
        $discussion = $this->discussions->query()
            ->where('discussions.id', $id)
            ->whereNotNull('discussions.hidden_at')
            ->first();

        if (! $discussion) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        if (! $actor->isAdmin() && ! $actor->hasPermission('recycleBin.deleteOwnForever')) {
            return new JsonResponse(['error' => 'permission_denied'], 403);
        }

        if (! $actor->isAdmin()) {
            if ((int) $discussion->user_id !== (int) $actor->id) {
                return new JsonResponse(['error' => 'own_content_only'], 403);
            }

            $hasOtherAuthors = $discussion->posts()
                ->where('user_id', '<>', $actor->id)
                ->where('type', 'comment')
                ->exists();

            if ($hasOtherAuthors) {
                return new JsonResponse(['error' => 'discussion_has_other_authors'], 409);
            }
        }

        $this->db->transaction(function () use ($discussion) {
            $discussion->posts()->get()->each(fn ($post) => $post->delete());
            $discussion->delete();
        });

        return new JsonResponse(['success' => true, 'id' => $id]);
    }
}
