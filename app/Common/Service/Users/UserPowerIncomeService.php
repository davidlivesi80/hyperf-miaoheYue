<?php

namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserPowerIncomeLogic;
use Hyperf\DbConnection\Db;

class UserPowerIncomeService extends BaseService
{


    /**
     * @var UserPowerIncomeLogic
     */
    public function __construct(UserPowerIncomeLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);

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