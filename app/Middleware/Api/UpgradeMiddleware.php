<?php

namespace App\Middleware\Api;

use App\Common\Service\System\SysConfigService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Upp\Traits\HelpTrait;


class UpgradeMiddleware implements MiddlewareInterface
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

         $web_switch =  $this->app(SysConfigService::class)->value('web_switch');
         $web_whilte_ids = explode('@',$this->app(SysConfigService::class)->value('web_whilte_ids'));
         $queryParams = $request->getQueryParams();
         if ($web_switch == 0) {
             if(isset($queryParams['userId'])){
                if(!in_array($queryParams['userId'],$web_whilte_ids)){
                    return $this->json("system_update",400);//系统升级
                }
             }else{
                 return $this->json("system_update",400);//系统升级
             }
         }

        return $handler->handle($request);

    }

}