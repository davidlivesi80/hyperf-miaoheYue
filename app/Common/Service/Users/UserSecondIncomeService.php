<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSecondIncomeLogic;
use Hyperf\DbConnection\Db;


class UserSecondIncomeService extends BaseService
{

    /**
     * @var UserSecondIncomeLogic
     */
    public function __construct(UserSecondIncomeLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','order:id,order_sn','second:id,market,status'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with('user:id,username,email,mobile,is_bind')->get();

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