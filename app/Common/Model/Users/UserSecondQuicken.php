<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use App\Common\Model\System\SysSecond;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserSecondQuicken extends BaseModel
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
        return 'user_second_quicken';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','income_id','second_id','target_id','symbol','rate','total','reward','reward_type','reward_time','settle_time'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function income()
    {
        return $this->hasOne(UserSecondIncome::class,'id','income_id');
    }

    public function target()
    {
        return $this->hasOne(User::class,'id','target_id');
    }

    public function second()
    {
        return $this->hasOne(SysSecond::class,'id','second_id');
    }




}