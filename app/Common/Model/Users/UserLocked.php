<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserLocked extends BaseModel
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
        return 'user_locked';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','lock_type','lock_num'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }



}