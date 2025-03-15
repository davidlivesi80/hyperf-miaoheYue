<?php


namespace App\Common\Logic\System;

use App\Common\Model\System\SysExchange;
use Upp\Basic\BaseLogic;

class SysExchangeLogic extends BaseLogic
{
    /**
     * @var SysPower
     */
    protected function getModel(): string
    {
        return SysExchange::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {

            return $query->where('paid_coin', $where['paid']);

        })->when(isset($where['give']) && $where['give'] !== '', function ($query) use ($where) {

            return $query->where('give_coin',  $where['give']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {

            return $query->where('status', $where['status']);

        })->orderBy('sort', 'desc');

        return $query;
    }


}