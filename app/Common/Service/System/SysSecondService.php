<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysSecondLogic;

class SysSecondService extends BaseService
{
    /**
     * @var SysSecondLogic
     */
    public function __construct(SysSecondLogic $logic)
    {
        $this->logic = $logic;
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
    public  function searchApi($market=""){
        $list = $this->logic->cacheableSecond();
        if($market){
            $marketIds = array_column($list,'market');
            $marketKey = array_search(strtolower($market),$marketIds);
            if($marketKey === false){return "";}
            return $list[$marketKey];
        }
        return $list;
    }

    /**
     * 查询搜索
     */

    public  function searchExp(array $where){
        $list = $this->logic->search($where)->with('increase')->get();
        return $list;
    }



}