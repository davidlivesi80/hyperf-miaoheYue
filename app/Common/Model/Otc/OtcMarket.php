<?php

declare (strict_types=1);
namespace App\Common\Model\Otc;


use App\Common\Model\Users\User;
use App\Common\Model\Users\UserExtend;
use Upp\Basic\BaseModel;

class OtcMarket extends BaseModel
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
        return 'otc_market';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {

        return [
            'order_sn','user_id','side','otc_coin_id','otc_coin_name','min_num','max_num','order_nums','order_amount','price','publish_time','run_time'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id')->with('extend:user_id,avatar,nickname');
    }






}