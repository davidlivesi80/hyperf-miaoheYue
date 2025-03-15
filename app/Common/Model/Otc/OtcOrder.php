<?php

declare (strict_types=1);
namespace App\Common\Model\Otc;


use App\Common\Model\Users\User;
use Upp\Basic\BaseModel;

class OtcOrder extends BaseModel
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
        return 'otc_order';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {

        return [
            'market_id','seller_uid','buyer_uid','other_uid','users_uid','side','otc_coin_name','otc_coin_id','number','price',
            'pay_coin','total_amount','total_price','order_time','pay_time','deal_time','status'
        ];
    }

    public function seller(){
        return $this->hasOne(User::class,'id','seller_uid')->with('extend:user_id,avatar,nickname');
    }
    public function buyer(){
        return $this->hasOne(User::class,'id','buyer_uid')->with('extend:user_id,avatar,nickname');
    }
    public function user(){
        return $this->hasOne(User::class,'id','users_uid')->with('extend:user_id,avatar,nickname');
    }
    public function target(){
        return $this->hasOne(User::class,'id','other_uid')->with('extend:user_id,avatar,nickname');
    }
    public function market(){
        return $this->hasOne(OtcMarket::class,'id','market_id')->with('extend:user_id,avatar,nickname');
    }


}