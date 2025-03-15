<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;

class SysLotteryAttr extends BaseModel
{
    
    /**
     * 自定义属性
     */
    protected $appends  = ['options_arr'];
    
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
        return 'sys_lottery_attr';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'attr_name','attr_value','attr_type','attr_unit'
        ];
    }
    
    /**
     * @return string
     */
    public function getOptionsArrAttribute()
    {

        if($this->attr_value){
            return explode("@",$this->attr_value);
        }else {
            return [];
        }   
    }


}