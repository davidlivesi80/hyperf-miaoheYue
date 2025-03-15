<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserMessageLogic;
use Upp\Exceptions\AppException;

class UserMessageService extends BaseService
{
    /**
     * @var UserMessageLogic
     */
    public function __construct(UserMessageLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 添加

     */

    public function create($userId,$data){

        $menu = $this->logic->getQuery()->where('user_id',$userId)->whereNull('reply')->count();
        if ($menu >= 5) {
            throw new AppException('messsage_exist',400);//信息已提交,等待回复
        }
        $data['user_id']   =  $userId;
        return $this->logic->create($data);

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('username','is_bind','email','mobile','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;

    }

}