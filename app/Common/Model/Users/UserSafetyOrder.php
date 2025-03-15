<?php
declare (strict_types=1);

namespace App\Common\Model\Users;


use App\Common\Model\System\SysSafety;
use Upp\Basic\BaseModel;


class UserSafetyOrder extends BaseModel
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
        return 'user_safety_order';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_sn','safety_id','usecond_id','amount','total','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function safety()
    {
        return $this->hasOne(SysSafety::class,'id','safety_id');
    }



}
