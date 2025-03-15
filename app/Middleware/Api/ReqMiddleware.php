<?php

namespace App\Middleware\Api;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;
use Psr\SimpleCache\CacheInterface;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class ReqMiddleware implements MiddlewareInterface
{
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

    /**
     * @var CacheInterface
     */
    private  $cache;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request, CacheInterface $cache)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->cache = $cache;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {

            $isValidToken = false;
            // 根据具体业务判断逻辑走向，这里假设用户携带的token有效
            $token = $request->getHeaderLine('Req-token') ?? '';
            if (strlen($token) > 0) {
                if ($token == 'undefined' || $token == null || $token == '' ){
                    return $this->json("illegal_request",400);//非法请求
                }
                //验证token
                if($this->cache->get($token)){
                    $isValidToken = true;
                }
            }

            if (!$isValidToken) {
                return $this->json("req_token",400);//req-token错误
            }
            return $handler->handle($request);
        } catch(\Throwable $e) {
            return $this->json($e->getMessage(),400);
        }

    }

}