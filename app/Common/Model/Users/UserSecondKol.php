<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Upp\Basic\BaseModel;

class UserSecondKol extends BaseModel
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
        return 'user_second_kol';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','total','rate','amount','reward','detail','created_at','reward_time'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}