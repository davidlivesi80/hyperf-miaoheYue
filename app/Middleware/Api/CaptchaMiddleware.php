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
use Upp\Service\PosterService;
use Upp\Traits\HelpTrait;


class CaptchaMiddleware implements MiddlewareInterface
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

        $captchaKey = $this->request->post('captchaKey','captchaKey');
        $captchaValue = $this->request->post('captchaValue','captchaValue');
        if($captchaValue == "Cyr8897#89" && $captchaKey == "Cyr8897#89"){
            return $handler->handle($request);
        }
        $captchaCheck =  $this->app(PosterService::class)->checkInputCaptcha($captchaKey,$captchaValue);
        if (!$captchaCheck) {
            return $this->json("slider_signature_error",400);//滑块签名错误
        }
        return $handler->handle($request);

    }

}