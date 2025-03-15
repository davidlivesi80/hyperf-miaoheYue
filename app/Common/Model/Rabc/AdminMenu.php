<?php

declare (strict_types=1);

namespace App\Common\Model\Rabc;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class AdminMenu extends BaseModel
{
    use SoftDeletes;
    /**
     * 关闭时间错
     */
    public $timestamps = false;

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
        return 'admin_menu';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'title','path','component','authority','menuType','openType','icon','sort','hide','parentId'
        ];
    }


}