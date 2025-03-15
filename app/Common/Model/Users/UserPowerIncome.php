<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;

class UserPowerIncome extends BaseModel
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
        return 'user_power_income';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','robot_id','symbol','rate','price','pools_wld','pools_atm','reward_wld','reward_atm','reward_type','reward_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}