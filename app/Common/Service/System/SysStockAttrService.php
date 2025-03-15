<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysStockAttrLogic;

class SysStockAttrService extends BaseService
{
    /**
     * @var SysStockAttrLogic
     */
    public function __construct(SysStockAttrLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where){

        $list = $this->logic->search($where)->with(['attr'=>function($query){
       
        	return $query->select('attr_name','id');
        	
        }])->get();
        

        return $list;
    }

    
}