<?php

namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserExtendLogic;

class UserExtendService extends BaseService
{
    /**
     * @var UserExtendLogic
     */
    public function __construct(UserExtendLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with(['user' => function ($query) {

            return $query->select('username', 'id');

        }])->paginate($perPage, ['*'], 'page', $page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->paginate($perPage, ['*'], 'page', $page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }


}