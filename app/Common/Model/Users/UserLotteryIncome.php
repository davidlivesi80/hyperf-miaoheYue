<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use App\Common\Model\System\SysLottery;
use Upp\Basic\BaseModel;

class UserLotteryIncome extends BaseModel
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
        return 'user_lottery_income';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_id','lottery_id','target_id','symbol','rate','total','reward','reward_type','reward_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function userExtend()
    {
        return $this->hasOne(UserExtend::class,'user_id','user_id');
    }

    public function order()
    {
        return $this->hasOne(UserRobot::class,'id','order_id');
    }

    public function lottery()
    {
        return $this->hasOne(SysLottery::class,'id','second_id');
    }



}