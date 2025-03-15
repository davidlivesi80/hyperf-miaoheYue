<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserPowerOrder extends BaseModel
{
    use HelpTrait;

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
        return 'user_power_order';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_sn','symbol','total','total_num','total_oso','status','buy_time','buy_type','pay_time','pay_type','income_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}