<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysLotteryAttrLogic;

class SysLotteryAttrService extends BaseService
{

    /**
     * @var SysLotteryAttrLogic
     */
    public function __construct(SysLotteryAttrLogic $logic)
    {
        $this->logic = $logic;
    }

    public function unit()
    {
        return [
            ['id'=>1,'title'=>'个位'],

            ['id'=>2,'title'=>'十位'],

        ];
    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }
    
    /**
     * 查询搜索
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get();

        return $list;
    }
    
    public function getQueryArr(array $where){
        
        $list = $this->logic->search($where)->get()->toArray();
        
        $_key = [];
        
        foreach ($list as $val){
            $_key[] = 'attr_' . $val['id'];
        }
       
        return $_key;
    }




}