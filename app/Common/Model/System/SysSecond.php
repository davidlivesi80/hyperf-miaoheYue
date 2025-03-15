<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use App\Common\Model\Users\User;
use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysSecond extends BaseModel
{

    use SoftDeletes;

    protected $appends  = ['trade_period_arr'];

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
        return 'sys_second';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'market','symbol','currency','status','sort','trade_forbid','trade_period','tops'
        ];
    }

    /**
     * @return string
     */
    public function getTradePeriodArrAttribute()
    {
        if($this->trade_period){
            return explode('@',$this->trade_period);
        }else{
            return [];
        }
    }

    public function increase()
    {
        return $this->hasOne(SysMarkets::class,'second_id','id');
    }


}