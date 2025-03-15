<?php


namespace App\Common\Service\Rabc;

use Upp\Basic\BaseService;
use App\Common\Logic\Rabc\UserGroupLogic;

class UserGroupService extends BaseService
{

    /**
     * @var UserGroupLogic
     */
    public function __construct(UserGroupLogic $logic)
    {
        $this->logic = $logic;
    }

    public function create($userId, $groupId)
    {

        if ($this->logic->getQuery()->where(['user_id'=>$userId,'group_id'=>$groupId])->exists()) {

            return true;

        }

        return $this->logic->create(['user_id'=>$userId,'group_id'=>$groupId]);

    }



}