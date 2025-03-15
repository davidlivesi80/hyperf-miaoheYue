<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;

class SysLotteryAttrValue extends BaseModel
{

    
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
        return 'sys_lottery_attr_value';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'robot_id','attr_id','value'
        ];
    }

    public function attr()
    {
        return $this->hasOne(SysLotteryAttr::class,'id','attr_id');
    }




}