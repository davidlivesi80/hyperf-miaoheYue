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

use Upp\Traits\HelpTrait;


class LimitMiddleware implements MiddlewareInterface
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


    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $this->getRealIp( $this->request);
        $ip  = is_array($ip) ? $ip[0]:$ip;
        $ip_lock =  $this->getCache()->get('ip_lock_' . $ip);
        if($ip_lock){
            return $this->json('try_later',400);
        }
        $this->getCache()->set('ip_lock_' . $ip, 10, 1);

        return $handler->handle($request);
    }


}