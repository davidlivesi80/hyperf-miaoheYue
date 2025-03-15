<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysLotteryList extends BaseModel
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
        return 'sys_lottery_list';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'sn','lottery_id','title','number','price','kline'
        ];
    }



}