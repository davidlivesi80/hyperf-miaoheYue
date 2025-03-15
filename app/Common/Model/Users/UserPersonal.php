<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Upp\Basic\BaseModel;

class UserPersonal extends BaseModel
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
        return 'user_personal';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','real_name','card_id','card_right','card_left','is_personal'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}