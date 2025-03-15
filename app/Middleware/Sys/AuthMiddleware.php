<?php

declare(strict_types=1);

namespace App\Middleware\Sys;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use App\Common\Service\Rabc\UsersService;
use Upp\Traits\HelpTrait;

class AuthMiddleware implements MiddlewareInterface
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
     * @var UsersService
     */
    private  $usersService;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request , UsersService $usersService)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->usersService = $usersService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $this->request->path();
        
        $adminId = $this->request->query('adminId');

        // 根据具体业务判断逻辑走向
        $isValidToken = $this->usersService->auther($adminId,$path);

        if (!$isValidToken) {

            return $this->json('权限不足',403);
        }

        return $handler->handle($request);

    }
}