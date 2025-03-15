<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserMessage extends BaseModel
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
        return 'user_message';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','title','content','image'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

}