<?php


namespace App\Common\Logic\System;


use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysLottery;

class SysLotteryLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysLottery::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {

            return $query->where('id',$where['id']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where){

            return $query->where('status', $where['status']);

        })->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where){

            return $query->where('is_show', $where['is_show']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('title', 'like', "%".$where['title']."%");

        })->orderBy('sort', 'desc');

        return $query;
    }
}