<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserExchange extends BaseModel
{
    use SoftDeletes;
    /**
     * 自定义属性
     */
    protected $appends  = ['order_state'];

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
        return 'user_exchange';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','order_sn','exchange_id','order_give_number','order_paid_number','order_rate_number','order_give_coin','order_paid_coin','order_paid','order_amount'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    /**
     * @return string
     */
    public function getOrderStateAttribute()
    {

        if($this->order_paid == 1){

            return '已完成';

        }else{
            return '待支付';
        }

    }



}