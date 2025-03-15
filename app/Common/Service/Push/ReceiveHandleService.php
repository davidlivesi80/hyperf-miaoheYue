<?php
declare(strict_types=1);

namespace App\Common\Service\Push;

use App\Common\Service\Subscribe\ChannelRecord;
use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\ChannelService;
use App\Common\Service\Subscribe\SocketChannel;
use App\Constant\ChannelTimeConstant;
use App\Constant\PushEventConstant;
use App\Constant\PushModeConstant;
use Carbon\Carbon;
use Hyperf\Utils\Coroutine;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Upp\Traits\HelpTrait;

class ReceiveHandleService
{
    use HelpTrait;
    // 消息事件绑定
    const EVENTS = [
        PushEventConstant::EVENT_TALK          => 'onTalk',
    ];

    /**
     * 频道消息回调
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onTalk($server, Frame $frame, $data)
    {
        if(!isset($data['channel'])){
            return;
        }
        // 验证消息类型
        if (!in_array($data['talk_type'], PushModeConstant::getTypes())) return;
        if($data['talk_type'] == 4){
            $this->onTalkSubscribe($server,$frame,$data);
        }elseif($data['talk_type'] == 5){
            $this->onTalkHistory($server,$frame,$data);
        }elseif($data['talk_type'] == 6){
            $this->onTalkUnSubscribe($server,$frame,$data);
        }elseif($data['talk_type'] == 3){
            $this->onConsumeTalk($server,$frame,$data);
        }
    }

    /**
     * 订阅频道回调
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onTalkSubscribe($server, Frame $frame, $data)
    {
        //清除频道
        $channelService = $this->app(ChannelService::class);
        $channelList = $channelService->lists();
        foreach ($channelList as $channel){
            if($channelService->isMember($frame->fd,$channel)){
                $channelService->quit($frame->fd,$channel);
            }
        }

        // 加入新频道
        if($frame->fd && $channelService->isDismiss($data['channel'])){
            $channelService->invite($frame->fd,$data['channel']);
        }
        $server->push($frame->fd, $this->toJson(PushEventConstant::EVENT_TALK, [
            'sender_id'   => 0,
            'receiver_id' => $data['channel'],
            'talk_type'   => PushModeConstant::CHANNEL_SUBSCRIBE,
            'data'        => 'ok'
        ]));
    }

    public function onTalkUnSubscribe($server, Frame $frame, $data)
    {
        //清除频道
        $channelService = $this->app(ChannelService::class);
        if($channelService->isMember($frame->fd,$data['channel'])){
            $channelService->quit($frame->fd,$data['channel']);
        }
        $server->push($frame->fd, $this->toJson(PushEventConstant::EVENT_TALK, [
            'sender_id'   => 0,
            'receiver_id' => $data['channel'],
            'talk_type'   => PushModeConstant::CHANNEL_SUBSCRIBE,
            'data'        => 'ok'
        ]));
    }

    /**
     * 获取历史数据
     *
     * @param Response|Server $server
     * @param Frame           $frame
     * @param array|string    $data 解析后数据
     * @return void
     */
    public function onTalkHistory($server, Frame $frame, $data)
    {
        //获取历史数据 string $key="",,string $start, string $end,
        $recordIds = $this->app(ChannelRecord::class)->range($data['channel'],$data['start'],$data['end']);
        $record = [];
        foreach ($recordIds as $recordid){
            $recordIdKey = $data['channel'] .":". $recordid;
            $result = $this->app(ChannelRecordData::class)->get($recordIdKey,'time','market','type','open','close','low','high','vol','count','amount','ts');
            if($result){
                $record[] = $result;
            }
        }
        $server->push($frame->fd,  $this->toJson(PushEventConstant::EVENT_TALK, [
            'sender_id'   => 0,
            'receiver_id' => $data['channel'],
            'talk_type'   => PushModeConstant::CHANNEL_HISTORY,
            'data'        => $record
        ]));
    }

    /**
     * 推送消息
     * @param array $data 队列消息
     */
    public function onConsumeTalk($server, Frame $frame, array $data): void
    {
        $now = Carbon::now();
        if(!isset($data['period'])){
            $data['period']  = "1min";
        }
        if ($data['period'] == "1min"){//1分钟
            $time = $now->startOfMinute()->timestamp;
        }elseif($data['period'] == "5min"){//5分钟
            $hour = $now->startOfHour()->timestamp;
            for ($i=0; $i <= 11; $i++){
                $timestamp = $i *  5 * 60;
                if(time() >= $hour + $timestamp){
                    $time = $hour + $timestamp;
                }
            }
        }elseif($data['period'] == "15min"){//15分钟
            $hour = $now->startOfHour()->timestamp;
            for ($i=0; $i <= 3; $i++){
                $timestamp = $i *  15 * 60;
                if(time() >= $hour + $timestamp){
                    $time = $hour + $timestamp;
                }
            }
        }elseif($data['period'] == "30min"){//30分钟
            $hour = $now->startOfHour()->timestamp;
            for ($i=0; $i <= 1; $i++){
                $timestamp = $i *  30 * 60;
                if(time() >= $hour + $timestamp){
                    $time = $hour + $timestamp;
                }
            }
        }elseif($data['period'] == "60min"){//60分钟
            $time = $now->startOfHour()->timestamp;
        }elseif($data['period'] == "1day"){//日线
            $time = $now->startOfDay()->timestamp;
            $time = $time + (8 * 3600);
        }elseif($data['period'] == "1week"){//周线
            $time = $now->startOfWeek()->timestamp;
        }elseif($data['period'] == "1mon"){//月线
            $time = $now->startOfMonth()->timestamp;
        }else {
            $time = $now->startOfMinute()->timestamp;
        }

        $recordId  =  $data['channel'].":". $time;

        //获取消息
        $result = $this->app(ChannelRecordData::class)->get($recordId,'time','market','type','open','close','low','high','vol','count','amount','ts');
        if (!$result['close']) {
            Coroutine::sleep(0.01);
            $result = $this->app(ChannelRecordData::class)->get($recordId,'time','market','type','open','close','low','high','vol','count','amount','ts');
        };
        try {
            //推送消息
            $server->push($frame->fd, $this->toJson(PushEventConstant::EVENT_TALK, [
                'sender_id'   => 0,
                'receiver_id' => $data['channel'],
                'talk_type'   => PushModeConstant::CHANNEL_CHAT,
                'data'        => $result
            ]));

        } catch(\Throwable $e){

        }
    }


    private function toJson(string $event, array $data): string
    {
        return json_encode(["event" => $event, "content" => $data]);
    }


}
