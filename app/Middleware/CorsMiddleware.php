<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Hyperf\Utils\Context;


class CorsMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        $response = $response->withHeader('Access-Control-Allow-Origin', '*')
            // 设置是否允许发送 cookies
            // 设置允许自定义请求头的字段
            ->withHeader('Access-Control-Allow-Headers', 'Authori-zation,Req-token,Req-lang,Keep-Alive,User-Agent,Cache-Control,Content-Type,Apptype,apptype,wallet-zation,contenttype');

        Context::set(ResponseInterface::class, $response);

        if ($request->getMethod() == 'OPTIONS' || $request->getUri()->getPath() == '/favicon.ico') {
            return $response;
        }

        return $handler->handle($request);

    }
}