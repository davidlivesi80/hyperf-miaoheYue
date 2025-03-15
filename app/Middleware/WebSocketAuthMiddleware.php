<?php
declare(strict_types=1);

/**
 * This is my open source code, please do not use it for commercial applications.
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author Yuandong<837215079@qq.com>
 * @link   https://github.com/gzydong/hyperf-chat
 */

namespace App\Middleware;


use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Upp\Traits\HelpTrait;
use Hyperf\HttpServer\Contract\ResponseInterface as HyperfResponseInterface;

/**
 * WebSocket token 授权验证中间件
 *
 * @package App\Middleware
 */
class WebSocketAuthMiddleware implements MiddlewareInterface
{
    use HelpTrait;
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 授权验证拦截握手请求并实现权限验证
//        $isValidToken = false;
//        // 根据具体业务判断逻辑走向，这里假设用户携带的token有效
//        $token = $request->getHeaderLine('Req-token') ?? '';
//        if ($token == 'undefined' || $token == null || $token == '' ){
//            $this->stdout_log()->notice("Req-token不能为空");
//            return $this->container->get(HyperfResponseInterface::class)->raw('illegal_request');
//        }
//        //验证token
//        $fdId = $this->getCache()->get($token);
//        if($fdId){
//            $isValidToken = true;
//        }
//
//        if (!$isValidToken) {
//            $this->stdout_log()->notice("Req-token验证失败");
//            return $this->container->get(HyperfResponseInterface::class)->raw('req_token');
//        }
        return $handler->handle($request);
    }
}
