<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserFoundLogic;

class UserFoundService extends BaseService
{
    /**
     * @var UserFoundLogic
     */
    public function __construct(UserFoundLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['found'=>function($query){

            return $query->select('mobile','email','is_bind','id');

        }])->with(['user'=>function($query){

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