<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysCoins;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;

class SysCoinsLogic extends BaseLogic
{
    /**
     * @var SysCoins
     */
    protected function getModel(): string
    {
        return SysCoins::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['coin_symbol']) && $where['coin_symbol'] !== '', function ($query) use ($where){

            return $query->where('coin_symbol', $where['coin_symbol']);

        })->when(isset($where['coin_name']) && $where['coin_name'] !== '', function ($query) use ($where) {

            return $query->where('coin_name', 'like', "%".$where['coin_name']."%");

        })->orderBy('sort', 'desc');

        return $query;
    }


    public function column(array $where)
    {

        $query = $this->getQuery()->orderBy('sort', 'desc');

        return $query;
    }

    /**
     * @Cacheable(prefix="sys-coins", ttl=9000, listener="sys-coins-update")
     */
    public function cacheableCoins()
    {

        $lists =  $this->getQuery()->select(['coin_name','coin_symbol','usd','id'])->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-coins", ttl=9000)
     */
    public function cachePutCoins()
    {

        $lists =   $this->getQuery()->select(['coin_name','coin_symbol','usd','id'])->get()->toArray();

        return $lists;

    }
}