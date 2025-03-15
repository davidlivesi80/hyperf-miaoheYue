<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Upp\Basic\BaseModel;

class UserRechargeEx extends BaseModel
{
    /**
     * 关闭时间错
     */
    public $timestamps = false;

    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'recharge_id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'user_recharge_ex';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
             'recharge_id', 'pid', 'tx_id', 'from', 'to','status'
        ];
    }


}