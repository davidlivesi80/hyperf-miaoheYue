<?php

namespace App\Common\Service\Users;


use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRadotIncomeLogic;
use Hyperf\DbConnection\Db;

class UserRadotIncomeService extends BaseService
{


    /**
     * @var UserRadotIncomeLogic
     */
    public function __construct(UserRadotIncomeLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','order:id,order_sn'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with('user:id,username')->get();

        return $list;

    }

    /**
     * 统计奖励
     */
    public function reward($where){

        $res =  $this->logic->search($where)->first(array(Db::raw('sum(reward_wld) as wld'),Db::raw('sum(reward_atm) as atm')));
        return $res;
    }

}