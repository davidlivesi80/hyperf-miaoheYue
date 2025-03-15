<?php

declare(strict_types=1);

namespace App\Middleware\api;

use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

use Hyperf\Contract\TranslatorInterface;

class LangMiddleware implements MiddlewareInterface
{
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
     * @var TranslatorInterface
     */
    private  $translator;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request , TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->translator = $translator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 根据具体业务判断逻辑走向，这里假设用户携带的token有效
        $lang = $request->getHeaderLine('Req-lang') ?? 'en';

        $this->translator->setLocale($lang);

        return $handler->handle($request);

    }
}