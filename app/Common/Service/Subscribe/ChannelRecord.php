<?php

namespace App\Common\Service\Subscribe;

use Upp\Repository\ZSetRedis;

/**
 * 频道消息 - 缓存助手
 *
 * @package App\Cache
 */
class ChannelRecord extends ZSetRedis
{
    protected $name = 'market:kline';

    /**
     * 获取频道名
     *
     * @param string|integer $room 频道名
     * @return string
     */
    public function getRecordName(string $key)
    {
        return $this->getCacheKey($key);
    }

    /**
     * 获取频道所有的链接ID
     *
     * @param string $room 房间名
     * @return array
     */
    public function getRecordIds(string $key)
    {
        return $this->all($key);
    }

    /**
     * 添加消息ID
     *
     * @param string $room      频道名
     * @param string $member 用户ID
     * @return bool|int
     */
    public function addRecordId(string $key,float $score,string $member)
    {
        return $this->add($key,$member,$score);
    }

    /**
     *
     * 删除消息ID
     *
     * @param string $room      房间名
     * @param string ...$member 用户ID
     * @return int
     */
    public function delRecordId(string $key, string ...$member): int
    {
        return $this->rem($key, ...$member);
    }

    /**
     * 删除消息ID组
     *
     * @param string|int $room 房间名
     * @return int
     */
    public function delRecord(string $key): int
    {
        return $this->delete($key);
    }
}
