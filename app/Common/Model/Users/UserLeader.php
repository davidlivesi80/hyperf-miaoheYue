<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;


class UserLeader extends BaseModel
{

    protected $appends  = ['acl_total'];
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
        return 'user_leader';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        //用户 累计充值，今日充值，累计提现，今日提现，净入金，伞下余额，伞下注册，今日注册，伞下有效会员,数据时间
        return [
            'user_id','recharge','recharge_today','withdraw','withdraw_today','deposit','balance','regis_total','regis_today','user_xiao','date_at'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    /**
     * @return string
     */
    public function getAclTotalAttribute()
    {
        $acl_edus =  0;

        return $acl_edus;
    }


}