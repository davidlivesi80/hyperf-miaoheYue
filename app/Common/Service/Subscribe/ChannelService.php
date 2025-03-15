<?php

namespace App\Common\Service\Subscribe;

use App\Common\Service\System\SysSecondService;
use Upp\Basic\BaseService;
use App\Constant\ChannelTimeConstant;

class ChannelService  extends BaseService
{

    /**
     * 获取所有频道
     * @return array
     */
    public function lists()
    {
        $list = [];
        $markets = $this->app(SysSecondService::class)->searchApi();
        foreach ($markets as $market){
            foreach (ChannelTimeConstant::CHANNEL_TIMES as $time){
                $channel = $market['market'] . ':' .$time;
                if($this->isDismiss($channel)){
                    $list[] = $channel;
                }
            }
        }
        return $list;
    }

    /**
     * 创建频道
     * @param string $market 交易对
     * @param string $times 时间标记
     * @return bool
     */
    public function create(string $market,$times)
    {
        try {
            //创建频道
            $channel = $market .":".$times;
            SocketChannel::getInstance()->addRoomMember($channel, 0);
            return $channel;
        } catch (\Throwable $e) {
            var_dump($e->getMessage(),$e->getFile(),$e->getLine());
            return false;
        }
    }

    /**
     * 判断频道是否已存在
     *
     * @param int $channel 频道ID
     * @return bool
     */
    public function isDismiss(string $channel)
    {
        $channel =  SocketChannel::getInstance()->getRoomName($channel);

        return $this->getRedis()->exists($channel);
    }


    /**
     * 获取频道成员
     *
     * @param int   $fd    链接id
     * @param string $channel 频道ID
     * @return int
     */
    public function members( string $channel)
    {
        $member_ids = SocketChannel::getInstance()->getRoomMembers($channel);

        return $member_ids;
    }

    /**
     * 判断频道是否在该频道
     * @param int   $fd    链接id
     * @param string $channel 频道ID
     * @return int
     */
    public function isMember(int $fd, string $channel)
    {
        try {
            return SocketChannel::getInstance()->isMember($channel, strval($fd));
        } catch (\Throwable $e) {
            var_dump($e->getMessage(),$e->getFile(),$e->getLine());
            return false;
        }
    }

    /**
     * 解散频道
     *
     * @param int $channel_id 群ID
     * @param int $user_id  用户ID
     * @return bool
     */
    public function dismiss(string $channel)
    {
        try {
            //解散频道
            SocketChannel::getInstance()->delRoom(strval($channel));
            return true;
        } catch (\Throwable $e) {
            var_dump($e->getMessage(),$e->getFile(),$e->getLine());
            return false;
        }
    }

    /**
     * 加入频道
     *
     * @param int   $user_id    用户ID
     * @param int   $channel_id   聊天群ID
     * @param array $friend_ids 被邀请的用户ID
     * @return bool
     */
    public function invite(int $fd, string $channel)
    {
        try {
            //加入频道
            SocketChannel::getInstance()->addRoomMember($channel, strval($fd));
            return true;
        } catch (\Throwable $e) {
            var_dump($e->getMessage(),$e->getFile(),$e->getLine());
            return false;
        }
    }

    /**
     * 退出频道
     *
     * @param int $user_id  用户ID
     * @param int $channel_id 群组ID
     * @return bool
     */
    public function quit(int $fd, string $channel)
    {
        try {
            //移出频道
            SocketChannel::getInstance()->delRoomMember($channel, strval($fd));
            return true;
        } catch (\Throwable $e) {
            var_dump($e->getMessage(),$e->getFile(),$e->getLine());
            return false;
        }
    }


}