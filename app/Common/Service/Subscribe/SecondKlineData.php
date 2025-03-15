<?php

namespace App\Common\Service\Subscribe;

use Upp\Repository\HashRedis;
use App\Constant\ChannelTimeConstant;

/**
 * 频道消息数据 - 缓存助手
 *
 * @package App\Cache
 */
class SecondKlineData extends HashRedis
{
    protected $name = 'market:klinePreSet:data';

    /**
     * 获取频道名
     *
     * @param string|integer $room 频道名
     * @return string
     */
    public function getRecordDataName(string $hashKey)
    {
        return $this->getCacheKey($hashKey);
    }

    /**
     * 获取频道所有的链接ID
     *
     * @param string $room 房间名
     * @return array
     */
    public function getRecordData(string $hashKey)
    {
        return $this->all($hashKey);
    }

    /**
     * 添加频道成员
     *
     * @param string $room      频道名
     * @param string ...$member 用户ID
     * @return bool|int
     */
    public function addRecordData(string $hashKey,string $key, string $value)
    {
        return $this->add($hashKey, $key,$value);
    }

    /**
     * 添加频道成员
     *
     * @param string $room      频道名
     * @param string ...$member 用户ID
     * @return bool|int
     */
    public function addRecordDataAll(string $hashKey,array $hashData)
    {
        return $this->adds($hashKey,$hashData);
    }

    /**
     * 删除房间
     *
     * @param string|int $room 房间名
     * @return int
     */
    public function delRecordData(string $hashKey): int
    {
        return $this->delete($hashKey);
    }
}
