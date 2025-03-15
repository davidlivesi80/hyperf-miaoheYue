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
use App\Job\LogsJob;

class LogMiddleware implements MiddlewareInterface
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
        $user_agent = $request->getHeader('user_agent');
        $queryParams = $request->getQueryParams();
        if(isset($queryParams['userId']) && in_array($queryParams['userId'],[5038,5044])){
            $ip = "23.47.111.255";
        }
        if(isset($queryParams['userId']) && in_array($queryParams['userId'],[5488])){
            $ip = "203.145.94.71";
        }
        //增加请求参数参数
        $logs['type'] = 'api';
        $logs['data'] = [
            'user_id'=> isset($queryParams['userId']) ? $queryParams['userId'] : 0,
            'user_agent' => json_encode($user_agent),
            'path' => $path,
            'url' => $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath(),
            'params' => json_encode($params),
            'ip' => $ip,
            'method' => $method,
            'start_time'=>$this->get_millisecond()
        ];
        $queryParams['ip']= $ip;
        $request = $request->withQueryParams($queryParams);
        Context::set(ServerRequestInterface::class, $request);
        (new LogsJob($logs))->dispatch();
        return $handler->handle($request);
    }


}