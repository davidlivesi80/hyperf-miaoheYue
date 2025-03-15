<?php


namespace App\Common\Logic\System;

use App\Common\Model\System\SysMarkets;
use Upp\Basic\BaseLogic;

class SysMarketsLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysMarkets::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['market']) && $where['market'] !== '', function ($query) use ($where) {

            return $query->where('symbol', $where['market']);

        })->orderBy('id', 'desc');

        return $query;
    }


}