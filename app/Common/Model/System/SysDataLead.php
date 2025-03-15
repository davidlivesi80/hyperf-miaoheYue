<?php
declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;

class SysDataLead extends BaseModel
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
        return 'sys_data_lead';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id', 'username'
        ];
    }

}