<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRobotIncomeLogic;
use Hyperf\DbConnection\Db;


class UserRobotIncomeService extends BaseService
{

    /**
     * @var UserRobotIncomeLogic
     */
    public function __construct(UserRobotIncomeLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','order:id,order_sn,bili,timer_rate','ucard:id,card_sn,card_id,status,price,rate','target:id,username'])->paginate($perPage,['*'],'page',$page);

        $list->each(function ($item){

            if( $item->ucard_id){
                $item->ucard->card;
            }
            
            return $item;
        });

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

        $res =  $this->logic->search($where)->sum('reward');
        return $res;
    }



}