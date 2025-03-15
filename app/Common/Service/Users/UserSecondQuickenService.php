<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSecondQuickenLogic;
use Hyperf\DbConnection\Db;

class UserSecondQuickenService extends BaseService
{

    /**
     * @var UserSecondQuickenLogic
     */
    public function __construct(UserSecondQuickenLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','target:id,username,email,mobile,is_bind'])->paginate($perPage,['*'],'page',$page);

        $list->each(function ($item){
            if ($item['target']){
                $item['target']['email'] = $item['target']['email'] ? substr_replace($item['target']['email'], '****', 3, 4) : "";
                $item['target']['mobile'] = $item['target']['mobile'] ?  substr_replace($item['target']['mobile'], '****', 3, 4) : "";
            }
            return $item;
        });

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','target:id,username'])->get();

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