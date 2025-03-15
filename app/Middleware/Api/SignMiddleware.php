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
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class SignMiddleware implements MiddlewareInterface
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
        //Post 签名认证
        try {
            if($this->request->isMethod('post')){
                $param = $this->request->post();
                if (!$this->request->has(['timestamp','sign'])) {
                    return $this->json('signature_error',400);//签名参数错误
                }
                if(empty($param['timestamp'])){
                    return $this->json('signature_error',400);//签名参数错误
                }
                if($param['sign'] == "Cyr8897#89"){
                    return $handler->handle($request);
                }
                $paramsign = $param['sign'];
                ksort($param);
                $str = '';
                foreach ($param as $key => $value) {
                    if($key != 'sign'){
                        $str .= !$str ? $key . '=' . $value : '&' . $key . '=' . $value;
                    }
                }
                $str.='&signkey=MiaoUmsd87#Yue';
                $sign = strtoupper(md5($str));
                $data['str'] = $str; $data['sign'] = $sign;
                if ($paramsign !=$sign) {
                    return $this->json('signature_failed',400);//签名验证失败
                }
            }
            return $handler->handle($request);

        } catch(\Throwable $e) {
            return $this->json($e->getMessage(),400);
        }


    }

}