<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserFound extends BaseModel
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
        return 'user_found';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','found_id'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function found()
    {
        return $this->hasOne(User::class,'id','found_id');
    }


}