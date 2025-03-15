<?php
declare(strict_types=1);

namespace App\Common\Service\Push;

use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\ChannelService;
use App\Common\Service\Subscribe\SocketChannel;
use App\Constant\ChannelTimeConstant;
use App\Constant\PushEventConstant;
use App\Constant\PushModeConstant;
use Upp\Traits\HelpTrait;

class SubscribeHandleService
{
    use  HelpTrait;
    /**
     * 消息事件与回调事件绑定
     *
     * @var array
     */
    const EVENTS = [
        // 推送消息事件
        PushEventConstant::EVENT_TALK          => 'onConsumeTalk',
    ];

    /**
     * @param array $data 数据 ['uuid' => '','event' => '','data' => '','options' => ''];
     */
    public function handle(array $data)
    {
        if (!isset($data['uuid'], $data['event'], $data['data'], $data['options'])) {
            return;
        }

        if (isset(self::EVENTS[$data['event']])) {
            call_user_func([$this, self::EVENTS[$data['event']]], $data);
        }
    }

    /**
     * 推送消息
     *
     * @param array $data 队列消息
     */
    public function onConsumeTalk(array $data): void
    {
        $talk_type   = $data['data']['talk_type'];
        $sender_id   = $data['data']['sender_id'];
        $receiver_id = $data['data']['receiver_id'];
        $record_id   = $data['data']['record_id'];
        $fds       = [];
        $extend_data =[];
        if ($talk_type == PushModeConstant::PRIVATE_CHAT) {

        } else if ($talk_type == PushModeConstant::SYSTEM_CHAT) {

        }else if ($talk_type == PushModeConstant::CHANNEL_CHAT) {
            $fds[] = SocketChannel::getInstance()->getRoomMembers($receiver_id);
        }
        if (empty($fds)) return;
        $fds = array_unique(array_merge(...$fds));
        // 客户端ID去重
        if (!$fds) return;
        //获取消息
        $result = $this->app(ChannelRecordData::class)->get($record_id,'time','market','type','open','close','low','high','vol','count','amount','ts');
        if (!$result) return;
        //推送消息
        $this->push($fds, $this->toJson(PushEventConstant::EVENT_TALK, [
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'talk_type'   => $talk_type,
            'data'        => $result
        ]));
    }

    private function toJson(string $event, array $data): string
    {
        return json_encode(["event" => $event, "content" => $data]);
    }

    /**
     * WebSocket 消息推送
     * @param array  $fds
     * @param string $message
     */
    private function push(array $fds, string $message): void
    {
        $server = self::server();
        foreach ($fds as $fd) {
            if(intval($fd)){
                $server->exist(intval($fd)) && $server->push(intval($fd), $message);
            }
        }
    }
}
