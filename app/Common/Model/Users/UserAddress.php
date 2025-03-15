<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserAddress extends BaseModel
{
    use SoftDeletes;
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
        return 'users_address';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','mobile','consignee','city','city_code','address','is_default'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

}