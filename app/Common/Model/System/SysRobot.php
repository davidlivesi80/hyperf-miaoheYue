<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysRobot extends BaseModel
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
        return 'sys_robot';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'title','image','price','rate','lever','detail','type','status','is_show','sort'
        ];
    }

    public function card()
    {
        return $this->hasOne(SysCards::class,'id','type');
    }


}