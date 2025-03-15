<?php
declare(strict_types=1);

namespace Upp\Repository;

/**
 * Redis Hash
 */
class HashRedis extends AbstractRedis
{
    protected $prefix = 'rds-hash';

    protected $name = 'default';

    /**
     * 获取 Hash 值
     *
     * @param string $hashKey
     * @param string ...$key
     * @return array|string
     */
    public function get(string $hashKey="",string ...$keys)
    {
        if (func_num_args() == 1) {
            return (string)$this->redis()->hGet($this->getCacheKey($hashKey), $keys[0]);
        }

        return $this->redis()->hMGet($this->getCacheKey($hashKey), $keys);
    }

    /**
     * 设置 Hash 值
     *
     * @param string $hashKey
     * @param string     $key
     * @param string|int|any $value
     */
    public function add(string $hashKey="",string $key, $value)
    {
        return $this->redis()->hSet($this->getCacheKey($hashKey), $key, $value);
    }
    /**
     * 设置 Hash 值
     *
     * @param string  $hashKey
     * @param array  $value
     */
    public function adds(string $hashKey="", array $hashData)
    {
        return $this->redis()->hMSet($this->getCacheKey( $hashKey), $hashData);
    }

    /**
     * 删除 hash 值
     * @param string $hashKey
     * @param string ...$key
     * @return bool|int
     */
    public function rem(string $hashKey="",string ...$key)
    {
        return $this->redis()->hDel($this->getCacheKey( $hashKey), ...$key);
    }

    /**
     * 给指定元素累加值
     * @param string $hashKey
     * @param string $member 元素
     * @param int    $score
     * @return float
     */
    public function incr(string $hashKey="", string $member, int $score): float
    {
        return $this->redis()->hincrby($this->getCacheKey( $hashKey), $member, $score);
    }

    /**
     * 获取 Hash 中元素总数
     *
     * @param string $hashKey
     * @return int
     */
    public function count(string $hashKey=""): int
    {
        return (int)$this->redis()->hLen($this->getCacheKey( $hashKey));
    }

    /**
     * 获取 Hash 中所有元素
     *
     * @param string $hashKey
     * @return array
     */
    public function all(string $hashKey=""): array
    {

        return $this->redis()->hGetAll($this->getCacheKey( $hashKey));
    }

    /**
     * 判断 hash 表中是否存在某个值
     *
     * @param string $hashKey
     * @param string $key
     * @return bool
     */
    public function isMember(string $hashKey="",string $key): bool
    {
        return $this->redis()->hExists($this->getCacheKey( $hashKey), $key);
    }

    /**
     * 删除 Hash 表
     *
     * @param string $hashKey
     * @return bool
     */
    public function delete(string $hashKey=""): bool
    {
        return (bool)$this->redis()->del($this->getCacheKey($hashKey));
    }

    /**
     * 过期 Hash 表
     *
     * @param string $hashKey
     * @return bool
     */
    public function expire(string $hashKey="",$time = 0)
    {
        if($time > 0){
            return  $this->redis()->expire($this->getCacheKey($hashKey),$time);
        }
    }


}
