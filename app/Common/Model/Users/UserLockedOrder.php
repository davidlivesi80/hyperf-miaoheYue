<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserLockedOrder extends BaseModel
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
        return 'user_locked_order';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','order_type','order_num'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}