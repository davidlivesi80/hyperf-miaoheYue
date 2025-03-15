<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use App\Common\Model\System\SysSecond;
use Upp\Basic\BaseModel;

class UserSecondIncome extends BaseModel
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
        return 'user_second_income';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_id','second_id','target_id','symbol','rate','total','reward','reward_type','order_type','dnamic_time','groups_time','reward_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function order()
    {
        return $this->hasOne(UserSecond::class,'id','order_id');
    }

    public function second()
    {
        return $this->hasOne(SysSecond::class,'id','second_id');
    }
    

}