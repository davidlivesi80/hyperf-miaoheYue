<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysSafety extends BaseModel
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
        return 'sys_safety';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'title','price','status','sort','period'
        ];
    }



}