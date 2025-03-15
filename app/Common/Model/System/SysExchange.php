<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysExchange extends BaseModel
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
        return 'sys_exchange';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'give_coin','paid_coin','price','rate','min_num','max_num','remark','status','sort'
        ];
    }


    public function give()
    {
        return $this->hasOne(SysCoins::class,'coin_symbol','give_coin');
    }

    public function paid()
    {
        return $this->hasOne(SysCoins::class,'coin_symbol','paid_coin');
    }



}