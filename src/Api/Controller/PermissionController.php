<?php

namespace Walsgit\RecycleBin\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermissionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        return new JsonResponse([
            'canDeleteOwnForever' => $actor->isAdmin() || $actor->hasPermission('recycleBin.deleteOwnForever'),
        ]);
    }
}
