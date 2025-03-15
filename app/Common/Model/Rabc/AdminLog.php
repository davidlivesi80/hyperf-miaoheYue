<?php

declare (strict_types=1);

namespace App\Common\Model\Rabc;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class AdminLog extends BaseModel
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
        return 'admin_log';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'manage_id','path','params','ip','method'
        ];
    }

    public function user()
    {
        return $this->hasOne(AdminUser::class,'id','manage_id');
    }
}