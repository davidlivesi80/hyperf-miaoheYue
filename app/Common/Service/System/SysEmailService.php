<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysEmailLogic;

class SysEmailService extends BaseService
{
    /**
     * @var SysEmailLogic
     */
    public function __construct(SysEmailLogic $logic)
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
    public  function searchApi(){

        $list = $this->logic->cacheableEmail();

        return $list;
    }



}