<?php


namespace App\Common\Logic\Rabc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Rabc\AdminUserGroup;

class UserGroupLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return AdminUserGroup::class;
    }

}