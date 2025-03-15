<?php

declare (strict_types=1);
namespace App\Common\Model\System;


use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysLottery extends BaseModel
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
        return 'sys_lottery';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'title','image','price','number','attr_ids','detail','types','status','is_show','sort'
        ];
    }


    /*访问器*/
    public function getAttrIdsAttribute($value)
    {
        if($value){
            return explode(',',$value);
        }else{
            return [];
        }

    }

    /**
     * @return string
     */
    public function attres()
    {
        return $this->hasMany(SysLotteryAttrValue::class, 'lottery_id', 'id');
    }


}