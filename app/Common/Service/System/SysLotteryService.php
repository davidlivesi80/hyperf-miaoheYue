<?php


namespace App\Common\Service\System;



use Upp\Basic\BaseService;
use App\Common\Logic\System\SysLotteryLogic;

class SysLotteryService extends BaseService
{
    /**
     * @var SysLotteryLogic
     */
    public function __construct(SysLotteryLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['attres'=>function($query){

            return $query->select('value','attr_id','lottery_id','id')->with('attr:id,attr_name,attr_value,attr_type');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->with(['attres'=>function($query){

            return $query->select('value','attr_id','lottery_id','id')->with('attr:id,attr_name,attr_value,attr_type');

        }])->first();

        return $list;
    }


    /**
     * 查询搜索
     */
    public function searchCache(array $where){


        $first = $this->logic->search($where)->with(['attres'=>function($query){

            return $query->select('value','attr_id','lottery_id','id')->with('attr:id,attr_name,attr_value,attr_type');

        }])->first()->toArray();


        return $first;
    }



}