<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;

class UserRelation extends BaseModel
{
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
        return 'user_relation';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'uid','pid','pids'
        ];
    }

    public function parent()
    {
        return $this->hasOne(User::class,'id','pid');
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','uid');
    }

    public function balance()
    {
        return $this->hasOne(UserBalance::class,'user_id','uid');
    }

    public function extend()
    {
        return $this->hasOne(UserExtend::class,'user_id','uid');
    }
    
    public function counts()
    {
        return $this->hasOne(UserCount::class,'user_id','uid');
    }
    public function reward()
    {
        return $this->hasOne(UserReward::class,'user_id','uid');
    }

}