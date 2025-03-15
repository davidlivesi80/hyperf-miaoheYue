<?php


namespace App\Common\Logic\System;

use App\Common\Model\System\SysSafety;
use Upp\Basic\BaseLogic;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;
class SysSafetyLogic extends BaseLogic
{
    /**
     * @var SysSafety
     */
    protected function getModel(): string
    {
        return SysSafety::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('title', $where['title']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {

            return $query->where('status', $where['status']);

        })->orderBy('sort', 'desc');

        return $query;
    }

    /**
     * @Cacheable(prefix="sys-safety", ttl=9000, listener="sys-safety-update")
     */
    public function cacheableSafety()
    {

        $lists =  $this->getQuery()->orderBy('sort', 'desc')->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-safety", ttl=9000)
     */
    public function cachePutSafety()
    {

        $lists =   $this->getQuery()->orderBy('sort', 'desc')->get()->toArray();


        return $lists;

    }
}