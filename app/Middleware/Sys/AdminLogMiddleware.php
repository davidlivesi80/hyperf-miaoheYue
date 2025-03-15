<?php

namespace App\Middleware\Sys;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;
use Upp\Traits\HelpTrait;
use App\Job\LogsJob;

class AdminLogMiddleware implements MiddlewareInterface
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
        $uri = $request->getUri();
        $path = $uri->getPath();
        $host = $uri->getHost();
        $method = $this->request->getMethod();
        $params = !empty($this->request->all()) ?  $this->request->all() :"";
        $ip = $this->getRealIp( $this->request);
        $ip  = is_array($ip) ? $ip[0]:$ip;
        //增加请求参数参数
        $logs['type'] = 'sys';
        $logs['data'] = [
            'manage_id'=> isset($params['adminId']) ? $params['adminId'] : 0,
            'path' => $path,
            'params' => json_encode($params),
            'ip' => $ip,
            'method' => $method,
        ];
        (new LogsJob($logs))->dispatch();
        return $handler->handle($request);
    }


}