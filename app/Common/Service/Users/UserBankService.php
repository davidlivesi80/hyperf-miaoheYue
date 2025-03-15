<?php

namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserBankLogic;
use Upp\Exceptions\AppException;


class UserBankService extends BaseService
{

    /**
     * @var UserBankLogic
     */
    public function __construct(UserBankLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('username','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }



}