<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRobotQuickenLogic;
use Hyperf\DbConnection\Db;

class UserRobotQuickenService extends BaseService
{

    /**
     * @var UserRobotQuickenLogic
     */
    public function __construct(UserRobotQuickenLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','target:id,username'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with(['user:id,username','target:id,username'])->get();

        return $list;

    }

    /**
     * 统计奖励
     */
    public function reward($where){

        $res =  $this->logic->search($where)->sum('reward');
        return $res;
    }



}