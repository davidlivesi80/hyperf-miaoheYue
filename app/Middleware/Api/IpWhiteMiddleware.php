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
use App\Common\Service\System\SysConfigService;

class IpWhiteMiddleware implements MiddlewareInterface
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
        //$currentUrl = $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath();
        $ip = $this->getRealIp($this->request);
        $ip  = is_array($ip) ? $ip[0]:$ip;
        $wallet_ip = explode('|',env('IP_WHITE', ''));
        if(!in_array($ip,$wallet_ip)){
            return $this->json("illegal_ip" ,400);//非法IP访问
        }

        return $handler->handle($request);
    }


}