<?php

declare (strict_types=1);

namespace App\Common\Model\Rabc;

use Upp\Basic\BaseModel;

class AdminUserGroup extends BaseModel
{

    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'admin_user_group';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'group_id','user_id'
        ];
    }

}