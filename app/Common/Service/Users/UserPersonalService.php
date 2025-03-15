<?php

namespace App\Common\Service\Users;


use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserPersonalLogic;
use Upp\Exceptions\AppException;

class UserPersonalService extends BaseService
{

    /**
     * @var UserPersonalLogic
     */
    public function __construct(UserPersonalLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 检查用户名是否已存在

     */

    public function checkUser($username)
    {
        $user = $this->app(UserService::class)->findWhere('username',$username);

        if(!$user){
            throw new AppException('用户名不存在,请重新填写',400);
        }

        $res = $this->logic->fieldExists('user_id', $user->id);

        if($res){
            throw new AppException('用户名已申请,请重新填写',400);
        }

        return  $user->id;

    }
    
    /**

     * 添加

     */

    public function create($userId,$data){

        $menu = $this->logic->fieldExists('user_id',$userId);

        if ($menu) {

            throw new AppException('该用户已提交实名信息',400);

        }

        $data['user_id']   =  $userId;

        return $this->logic->create($data);

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with('user:id,username')->paginate($perPage,['*'],'page',$page);

        return $list;

    }


}