<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserBank extends BaseModel
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
        return 'user_bank';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','bank_type','bank_real','bank_account'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }


}