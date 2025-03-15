<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysLotteryAttrValue;

class SysLotteryAttrValueLogic extends BaseLogic
{
    /**
     * @var SysLotteryAttrValue
     */
    protected function getModel(): string
    {
        return SysLotteryAttrValue::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['robot_id']) && $where['robot_id'] !== '', function ($query) use ($where) {

            return $query->where('robot_id',$where['robot_id']);

        })->when(isset($where['ids']) && $where['ids'] !== '', function ($query) use ($where) {

            return $query->whereIn('attr_id',explode(',',$where['ids']));

        });

        return $query;
    }


}