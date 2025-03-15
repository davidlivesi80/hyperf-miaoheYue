<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;

class UserRadotIncome extends BaseModel
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
        return 'user_radot_income';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','order_id','robot_id','symbol','rate','price','counts','reward_wld','reward_atm','reward_type','reward_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function order()
    {
        return $this->hasOne(UserRadot::class,'id','order_id');
    }

}