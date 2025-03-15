<?php


namespace App\Common\Service\Users;

use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserLockedOrderLogic;

class UserLockedOrderService extends BaseService
{
    /**
     * @var UserLockedOrderLogic
     */
    public function __construct(UserLockedOrderLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询可用
     */
    public function searchBalance(array $where){

        $balance = $this->logic->search($where)->sum('lock_num');

        return $balance;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('mobile','email','is_bind','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('mobile','email','is_bind','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }


}