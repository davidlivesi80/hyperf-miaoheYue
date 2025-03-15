<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Hyperf\Utils\Context;


class XssMiddleware implements MiddlewareInterface
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


    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->request  =$request;

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //$userInput = $this->request->all();
        //var_dump($userInput);
//        array_walk_recursive($userInput, function (&$userInput) {
//            if(is_string($userInput)){
//                $userInput = (htmlspecialchars(htmlentities($userInput,ENT_QUOTES,'UTF-8')));
//            }
//
//        });
        //var_dump($userInput);
        //$request = $request->withQueryParams($userInput);
        return $handler->handle($request);
    }
}