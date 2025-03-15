<?php

declare (strict_types=1);

namespace App\Common\Model\Rabc;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class AdminGroup extends BaseModel
{
    use SoftDeletes;
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
        return 'admin_group';
    }


    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'group_name','group_code'
        ];
    }

}