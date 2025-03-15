<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysLotteryListLogic;

class SysLotteryListService extends BaseService
{
    /**
     * @var SysLotteryListLogic
     */
    public function __construct(SysLotteryListLogic $logic)
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
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get();

        return $list;
    }
    




}