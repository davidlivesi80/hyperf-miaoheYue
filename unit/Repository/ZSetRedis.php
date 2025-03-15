<?php
declare(strict_types=1);

namespace Upp\Repository;

/**
 * Class ZSetRedis
 */
class ZSetRedis extends AbstractRedis
{
    protected $prefix = 'rds-zset';

    protected $name = 'default';

    /**
     * 添加有序集合元素
     *
     * @param string $member
     * @param float  $score
     * @return int
     */
    public function add(string $key="",string $member, float $score): int
    {
        return $this->redis()->zAdd($this->getCacheKey($key), $score, $member);
    }

    /**
     * 删除有序集合元素
     *
     * @param string ...$member
     * @return int
     */
    public function rem(string $key="",string ...$member): int
    {
        return $this->redis()->zRem($this->getCacheKey($key), ...$member);
    }

    /**
     * 给指定元素累加分数
     *
     * @param string $member 元素
     * @param float  $score
     * @return float
     */
    public function incr(string $key="",string $member, float $score): float
    {
        return $this->redis()->zIncrBy($this->getCacheKey($key), $score, $member);
    }

    /**
     * 获取有序集合元素总数
     *
     * @return int
     */
    public function count(string $key=""): int
    {
        return $this->redis()->zCard($this->getCacheKey($key));
    }

    /**
     * 获取有序集合所有元素
     *
     * @param bool $asc      [true:从高到低排序，false:从低到高排序]
     * @param bool $is_score 是否需要分数值
     * @return array
     */
    public function all(string $key="",$asc = true, $is_score = true): array
    {
        return $this->redis()->{$asc ? 'zRevRange' : 'zRange'}($this->getCacheKey($key), 0, -1, $is_score);
    }

    /**
     * 获取排行榜单
     *
     * @param int  $page 分页
     * @param int  $size 分页大小
     * @param bool $asc  [true:从高到低排序，false:从低到高排序]
     * @return array
     */
    public function rank(string $key="",$page = 1, $size = 10, $asc = true): array
    {
        $count = $this->count();

        [$start, $end] = $asc ? ['+inf', '-inf'] : ['-inf', '+inf'];

        $rows = $this->redis()->{$asc ? 'zRevRangeByScore' : 'zRangeByScore'}($this->getCacheKey($key), $start, $end, [
            'withscores' => true,
            'limit'      => [($page - 1) * $size, $size]
        ]);

        $ranks = [];
        foreach ($rows as $node => $score) {
            $ranks[] = [
                'rank'  => $this->getMemberRank($key,$node, $asc),
                'node'  => $node,
                'score' => $score
            ];
        }

        return [
            'count' => $count,
            'page'  => $page,
            'size'  => $size,
            'ranks' => $ranks
        ];
    }

    /**
     * 获取指定区间分数
     *
     * @param string $start 最低分值
     * @param string $end   最高分值
     * @param array  $options  ['withscores' => TRUE, 'limit' => [1, 1]]  WITHSCORES修饰符表示元素和分数一起返回
     * @return array
     */
    public function range(string $key="",string $start, string $end, array $options = []): array
    {
        return $this->redis()->zRangeByScore($this->getCacheKey($key), $start, $end, $options);
    }

    /**
     * 获取指定元素的排名
     *
     * @param string $member 元素
     * @param bool   $asc    [true:从高到低排序，false:从低到高排序]
     * @return false|int
     */
    public function getMemberRank(string $key="",string $member, $asc = true)
    {
        return $this->redis()->{$asc ? 'zRevRank' : 'zRank'}($this->getCacheKey($key), $member);
    }

    /**
     * 获取指定元素的分数
     *
     * @param string $member 元素
     * @return bool|float
     */
    public function getMemberScore(string $key="",string $member)
    {
        return $this->redis()->zScore($this->getCacheKey($key), $member);
    }

    /**
     * 判断是否是集合元素
     *
     * @param string $member
     * @return bool
     */
    public function isMember(string $key="",string $member): bool
    {
        return $this->redis()->zScore($this->getCacheKey($key), $member);
    }

    /**
     * 删除 ZSet 有序集合表
     *
     * @return bool
     */
    public function delete(string $key=""): bool
    {
        return (bool)$this->redis()->del($this->getCacheKey($key));
    }
}
