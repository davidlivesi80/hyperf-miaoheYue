<?php
declare (strict_types=1);

namespace App\Common\Model\Users;



use Upp\Basic\BaseModel;


class UserSafetyCoupons extends BaseModel
{

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
        return 'user_safety_coupons';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','target_id','order_sn','order_type','number','total','status'];
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
