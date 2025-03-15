<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysConfig;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;

class SysConfigLogic extends BaseLogic
{
    /**
     * @var SysConfig
     */
    protected function getModel(): string
    {
        return SysConfig::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where,$order,$sort)
    {

        $query = $this->getQuery()->when(isset($where['types']) && $where['types'] !== '', function ($query) use ($where){

            return $query->where('types', $where['types']);

        })->orderBy($order, $sort);

        return $query;
    }

    /**
     * @Cacheable(prefix="sys-config", ttl=9000, listener="sys-config-update")
     */
    public function cacheableConfig()
    {

        $lists =  $this->getQuery()->pluck('value','key')->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-config", ttl=9000)
     */
    public function cachePutConfig()
    {

        $lists =   $this->getQuery()->pluck('value','key')->toArray();


        return $lists;

    }

}