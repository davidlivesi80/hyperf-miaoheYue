<?php

namespace App\Middleware\Api;

use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserRelationService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Upp\Traits\HelpTrait;


class FoundMiddleware implements MiddlewareInterface
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

        $queryParams = $request->getQueryParams();
        $parentIds = $this->app(UserRelationService::class)->getParent($queryParams['userId']);
        $web_whilte_ids = explode('@',$this->app(SysConfigService::class)->value('web_whilte_ids'));
        if(in_array(1,$parentIds)){
            if(!in_array($queryParams['userId'],$web_whilte_ids)){
                return $this->json("found_fail",400);//体验号禁止操作
            }
        }
        return $handler->handle($request);

    }

}