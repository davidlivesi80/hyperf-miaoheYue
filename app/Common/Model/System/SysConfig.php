<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysConfig extends BaseModel
{
    use SoftDeletes;
    /**
     * 关闭时间错
     */
    public $timestamps = false;

    /**
     * 自定义属性
    */
    protected $appends  = ['options_arr'];

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
        return 'sys_config';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'name','key','value','options','types','element','sort'
        ];
    }

    /**
     * @param  string  $value
     * @return string
     */
    public function getOptionsArrAttribute()
    {
        if($this->options){
            return array_map(function ($val){
                [$value, $label] = explode(':', $val, 2);
                return compact('value', 'label');
            },explode(PHP_EOL,$this->options));

        }else{
            return '';
        }

    }

  

}