<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserWithdraw extends BaseModel
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
        return 'user_withdraw';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','order_sn','order_coin','order_total','order_amount','bank_account','order_rate','order_mone','order_nei','order_type','order_exist','remark'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

}