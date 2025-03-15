<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysRobotLogic;

class SysRobotService extends BaseService
{
    /**
     * @var SysRobotLogic
     */
    public function __construct(SysRobotLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with('card:id,title,price')->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get();

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchCache(array $where){

        $list = $this->logic->search($where)->get();

        return $list;
    }



}