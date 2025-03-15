<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserTransfer extends BaseModel
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
        return 'user_transfer';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','target_id','order_sn','order_coin','order_amount','order_rate','order_mone','order_type','order_method','remark'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function target()
    {
        return $this->hasOne(User::class,'id','target_id');
    }

}