<?php

namespace Walsgit\RecycleBin\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermanentDeletePostController implements RequestHandlerInterface
{
    public function __construct(
        protected PostRepository $posts,
        protected ConnectionInterface $db
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $id = (int) ($request->getAttribute('id') ?? 0);
        if ($id === 0 && preg_match('~/posts/(\\d+)/permanent$~', $request->getUri()->getPath(), $matches)) {
            $id = (int) $matches[1];
        }
        $row = $this->db->table('posts')->where('id', $id)->whereNotNull('hidden_at')->first();
        $post = $row ? (new Post)->newFromBuilder((array) $row) : null;

        if (! $post) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        if (! $actor->isAdmin() && ! $actor->hasPermission('recycleBin.deleteOwnForever')) {
            return new JsonResponse(['error' => 'permission_denied'], 403);
        }

        if (! $actor->isAdmin() && ((int) $post->user_id !== (int) $actor->id || (int) $post->discussion->first_post_id === (int) $post->id)) {
            return new JsonResponse(['error' => 'own_content_only'], 403);
        }

        $this->db->transaction(fn () => $post->delete());

        return new JsonResponse(['success' => true, 'id' => $id]);
    }
}
