<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysStock;

class SysStockLogic extends BaseLogic
{
    /**
     * @var SysStock
     */
    protected function getModel(): string
    {
        return SysStock::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {
        if(isset($where['types']) && $where['types'] !==''){

            $query = $this->getQuery()->whereHas('card',function ($query) use($where){

                return $query->where('types', $where['types']);

            });

        }else{

            $query = $this->getQuery();
        }

        $query->when(isset($where['token_id']) && $where['token_id'] !== '', function ($query) use ($where) {

            return $query->where('token_id', $where['token_id']);

        })->when(isset($where['token_ids']) && $where['token_ids'] !== '', function ($query) use ($where) {

            return $query->whereIn('token_id', $where['token_ids']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {

            return $query->where('status', $where['status']);

        })->when(isset($where['locked']) && $where['locked'] !== '', function ($query) use ($where) {

            return $query->where('locked', $where['locked']);

        })->when(isset($where['freeze']) && $where['freeze'] !== '', function ($query) use ($where) {

            return $query->where('freeze', $where['freeze']);

        })->orderBy('token_id', 'desc');

        return $query;
    }





}