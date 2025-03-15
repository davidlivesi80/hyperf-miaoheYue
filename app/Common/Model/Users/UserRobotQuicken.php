<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserRobotQuicken extends BaseModel
{
    use HelpTrait;

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
        return 'user_robot_quicken';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_id','ucard_id','target_id','symbol','rate','total','reward','reward_type','reward_time'];
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