<?php

namespace App\Common\Service\Subscribe;

use Upp\Repository\SetGroupRedis;

/**
 * 频道 - 缓存助手
 *
 * @package App\Cache
 */
class SocketChannel extends SetGroupRedis
{
    protected $name = 'ws:channel';


    /**
     * 获取频道名
     *
     * @param string|integer $room 频道名
     * @return string
     */
    public function getRoomName($room)
    {
        return $this->getCacheKey($room);
    }

    /**
     * 获取频道所有的链接ID
     *
     * @param string $room 房间名
     * @return array
     */
    public function getRoomMembers(string $room)
    {
        return $this->all($room);
    }

    /**
     * 添加频道成员
     *
     * @param string $room      频道名
     * @param string ...$member 用户ID
     * @return bool|int
     */
    public function addRoomMember(string $room, string ...$member)
    {
        return $this->add($room, ...$member);
    }

    /**
     *
     * 删除频道成员
     *
     * @param string $room      房间名
     * @param string ...$member 用户ID
     * @return int
     */
    public function delRoomMember(string $room, string ...$member): int
    {
        return $this->rem($room, ...$member);
    }

    /**
     * 删除房间
     *
     * @param string|int $room 房间名
     * @return int
     */
    public function delRoom($room): int
    {
        return $this->delete($room);
    }
}
