<?php

namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserPowerLogLogic;


class UserPowerLogService extends BaseService
{
    /**
     * @var UserPowerLogService
     */
    public function __construct(UserPowerLogLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with(['user:id,username','target:id,username'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }


}