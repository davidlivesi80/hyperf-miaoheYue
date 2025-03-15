<?php

namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysStockAttr;

class SysStockAttrLogic extends BaseLogic
{
    /**
     * @var SysStockAttr
     */
    protected function getModel(): string
    {
        return SysStockAttr::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){
    	
        $query = $this->getQuery()->when(isset($where['token_id']) && $where['token_id'] !== '', function ($query) use($where){

            return $query->where('token_id', $where['token_id']);

        })->when(isset($where['card_id']) && $where['card_id'] !== '', function ($query) use($where){

            return $query->where('card_id', $where['card_id']);

        });

        return $query->orderBy('id', 'desc');

    }



}