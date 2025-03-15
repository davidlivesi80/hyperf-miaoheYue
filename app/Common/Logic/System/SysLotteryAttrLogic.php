<?php


namespace App\Common\Logic\System;


use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysLotteryAttr;

class SysLotteryAttrLogic extends BaseLogic
{
    /**
     * @var SysLotteryAttr
     */
    protected function getModel(): string
    {
        return SysLotteryAttr::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('attr_name',  'like', "%".$where['title']."%");

        })->when(isset($where['ids']) && $where['ids'] !== '', function ($query) use ($where) {

            return $query->whereIn('id',explode(',',$where['ids']));

        });

        return $query;
    }


}