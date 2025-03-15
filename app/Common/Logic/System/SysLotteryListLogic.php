<?php
namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysLotteryList;

class SysLotteryListLogic extends BaseLogic
{
    /**
     * @var SysLotteryList
     */
    protected function getModel(): string
    {
        return SysLotteryList::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['sn']) && $where['sn'] !== '', function ($query) use ($where) {

            return $query->where('sn', $where['sn']);

        })->when(isset($where['lottery_id']) && $where['lottery_id'] !== '', function ($query) use ($where) {

            return $query->where('lottery_id',$where['lottery_id']);

        })->when(isset($where['ids']) && $where['ids'] !== '', function ($query) use ($where) {

            return $query->where('id','<=',$where['ids']);

        })->orderBy('id','desc');

        return $query;
    }


}