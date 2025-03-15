<?php

declare (strict_types=1);
namespace App\Common\Model\Otc;


use Upp\Basic\BaseModel;

class OtcCoins extends BaseModel
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
        return 'otc_coins';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {

        return [
            'coin_name','coin_id','limit_max_number','limit_min_number','limit_max_price','limit_min_price','max_pub_num','rate'
        ];
    }

}