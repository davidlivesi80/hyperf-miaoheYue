<?php


namespace App\Common\Logic\System;

use App\Common\Model\System\SysSecondKline;
use Upp\Basic\BaseLogic;

class SysSecondKlineLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysSecondKline::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['market']) && $where['market'] !== '', function ($query) use ($where) {

            return $query->where('market', $where['market']);

        })->when(isset($where['second_id']) && $where['second_id'] !== '', function ($query) use($where){

            return $query->where('second_id', $where['second_id'] );

        })->orderBy('id', 'desc');

        return $query;
    }



}