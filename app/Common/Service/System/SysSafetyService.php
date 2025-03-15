<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysSafetyLogic;

class SysSafetyService extends BaseService
{
    /**
     * @var SysSafetyLogic
     */
    public function __construct(SysSafetyLogic $logic)
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
    public  function searchApi($safeId=0){
        $list = $this->logic->cacheableSafety();
        if($safeId){
            $safeIds = array_column($list,'id');
            $marketKey = array_search($safeId,$safeIds);
            if($marketKey === false){return "";}
            return $list[$marketKey];
        }
        return $list;
    }


}