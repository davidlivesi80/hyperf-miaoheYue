<?php

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class User extends BaseModel
{
    use SoftDeletes;


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
        return 'user';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'username','mobile_area','mobile','email','password','paysword','types','regis_ip','is_bind','is_lock','google2fa_secret','spread'
        ];
    }


    public function balance()
    {
        return $this->hasOne(UserBalance::class,'user_id','id');
    }

    /**业绩*/
    public function extend()
    {
        return $this->hasOne(UserExtend::class,'user_id','id');
    }

    /**业绩*/
    public function relation()
    {
        return $this->hasOne(UserRelation::class,'uid','id');
    }

    /**业绩*/
    public function counts()
    {
        return $this->hasOne(UserCount::class,'user_id','id');
    }

    /**奖金*/
    public function reward()
    {
        return $this->hasOne(UserReward::class,'user_id','id');
    }


}