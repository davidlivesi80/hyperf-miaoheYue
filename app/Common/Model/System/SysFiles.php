<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysFiles extends BaseModel
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
        return 'sys_files';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'cate_id','file_name','file_src','upload_type','user_type','user_id'
        ];
    }

}