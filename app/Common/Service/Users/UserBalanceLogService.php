<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserBalanceLogLogic;


class UserBalanceLogService extends BaseService
{
    /**
     * @var UserBalanceLogLogic
     */
    public function __construct(UserBalanceLogLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','target:id,username'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    

}