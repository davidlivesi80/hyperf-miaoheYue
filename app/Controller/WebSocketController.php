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

namespace App\Controller;


use App\Common\Service\Push\PushMessageService;
use App\Common\Service\Push\ReceiveHandleService;
use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\ChannelService;
use App\Constant\ChannelTimeConstant;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Contract\OnRequestInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use App\Event\PushEvent;
use Upp\Traits\HelpTrait;

/**
 * Class WebSocketController
 *
 * @package App\Controller
 */
class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    use HelpTrait;

    /**
     *频道
     */
    protected $channelService;

    /**
     *
     */
    protected $receiveHandle;

    public function __construct(ReceiveHandleService $receiveHandle, ChannelService $channelService)
    {
        $this->receiveHandle = $receiveHandle;
        $this->channelService = $channelService;
    }

    /**
     * 连接创建成功回调事件
     *
     * @param Response|Server $server
     * @param Request         $request
     */
    public function onOpen($server, Request $request): void
    {
        $server->push($request->fd, json_encode([
            "event"   => "connect",
            "content" => [
                "ping_interval" => 20,
                "ping_timeout"  => 20 * 3,
            ],
        ]));

        // 当前连接的用户
        $this->stdout_log()->notice("用户连接信息 : fd:{$request->fd} 时间：" . date('Y-m-d H:i:s'));
    }

    /**
     * 消息接收回调事件
     *
     * @param Response|Server $server
     * @param Frame           $frame
     */
    public function onMessage($server, Frame $frame): void
    {
        $result = json_decode($frame->data, true);
        if (!isset($result['event']) || empty($result['event'])){
            return;
        }
        // 判断是否为心跳检测
        if ($result['event'] == 'heartbeat') {

            $server->push($frame->fd, json_encode(['event' => "heartbeat", 'content' => "pong"]));
            return;
        }
        // 订阅K线
        if (!isset(ReceiveHandleService::EVENTS[$result['event']])) {
            return;
        }
        //回调处理
        call_user_func_array([$this->receiveHandle, ReceiveHandleService::EVENTS[$result['event']]], [
            $server, $frame, $result['data']
        ]);

    }

    /**
     * 连接创建成功回调事件
     *
     * @param Response|\Swoole\Server $server
     * @param int                     $fd
     * @param int                     $reactorId
     */
    public function onClose($server, int $fd, int $reactorId): void
    {
        $this->stdout_log()->notice("客户端FD:{$fd} 已关闭连接，关闭时间：" . date('Y-m-d H:i:s'));
        // 取消订阅
        $channelList = $this->channelService->lists();
        foreach ($channelList as $channel){
            if($this->channelService->isMember($fd,$channel)){
                $this->channelService->quit($fd,$channel);
            }
        }
    }
}
