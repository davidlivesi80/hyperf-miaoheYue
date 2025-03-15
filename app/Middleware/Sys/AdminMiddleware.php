<?php

declare(strict_types=1);

namespace App\Middleware\Sys;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Utils\Context;
use Upp\Service\ParseToken;

use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;


class AdminMiddleware implements MiddlewareInterface
{

    use RedisTrait;
    use HelpTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;


    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 根据具体业务判断逻辑走向，这里假设用户携带的token有效
        try {
            $Authori = $request->getHeaderLine('Authori-zation');
            $token=!empty($Authori) ? trim($request->getHeaderLine('Authori-zation')) : false;
            /** @var  $ParseToken ParseToken*/
            $parseToken = $this->app(ParseToken::class);
            [$key,$name,$type] = $parseToken->doToken($token,'sys');
            $queryParams = $request->getQueryParams();
            $queryParams['adminId']= $key;
            $request = $request->withQueryParams($queryParams);
            Context::set(ServerRequestInterface::class, $request);
            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->json($e->getMessage(),$e->getCode());
        }

    }


}