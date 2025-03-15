<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use App\Common\Model\Users\User;
use Upp\Basic\BaseModel;

class SysLogs extends BaseModel
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
        return 'sys_logs';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','ip','path','url','params','method','user_agent'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }


}