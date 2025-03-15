<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;

class SysMarkets extends BaseModel
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
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'sys_markets';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'second_id', 'coin', 'symbol'
        ];
    }

}