<?php


namespace App\Common\Service\Users;


use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRechargeExLogic;
use Upp\Traits\HelpTrait;

class UserRechargeServiceEx extends BaseService
{
    use HelpTrait;


    /**
     * @var UserRechargeExLogic
     */
    public function __construct(UserRechargeExLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){
        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);
        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){
        $list = $this->logic->search($where)->get();
        return $list;
    }

}