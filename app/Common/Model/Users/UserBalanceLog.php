<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class UserBalanceLog extends BaseModel
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
        return 'user_balance_log';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','target_id','source_id','coin','num','old','new','type','remark','source_id'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
    public function target()
    {
        return $this->hasOne(User::class,'id','target_id');
    }

}