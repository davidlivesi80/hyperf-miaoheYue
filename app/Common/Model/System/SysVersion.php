<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysVersion extends BaseModel
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
        return 'sys_version';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'type','ver_num','package_url','package_type','description','status'
        ];
    }
}