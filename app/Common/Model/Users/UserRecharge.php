<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserRecharge extends BaseModel
{
    use SoftDeletes;
    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'order_id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'user_recharge';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','recharge_id','order_sn','order_coin','order_amount','order_rate','order_mone','order_type','remark'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function extend()
    {
        return $this->hasOne(UserRechargeEx::class,'pid','order_id');
    }


}