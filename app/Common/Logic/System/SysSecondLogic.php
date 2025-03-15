<?php


namespace App\Common\Logic\System;

use App\Common\Model\System\SysSecond;
use Upp\Basic\BaseLogic;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;
class SysSecondLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysSecond::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['market']) && $where['market'] !== '', function ($query) use ($where) {

            return $query->where('market', $where['market']);

        })->when(isset($where['tops']) && $where['tops'] !== '', function ($query) use ($where) {

            return $query->where('tops', $where['tops']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {

            return $query->where('status', $where['status']);

        })->orderBy('sort', 'desc');

        return $query;
    }

    /**
     * @Cacheable(prefix="sys-second", ttl=9000, listener="sys-second-update")
     */
    public function cacheableSecond()
    {

        $lists =  $this->getQuery()->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-second", ttl=9000)
     */
    public function cachePutSecond()
    {

        $lists =   $this->getQuery()->get()->toArray();


        return $lists;

    }
}