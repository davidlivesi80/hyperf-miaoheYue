<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;

class SysStockAttr extends BaseModel
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
        return 'sys_stock_attr';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'token_id','card_id','attr_id','attr_value'
        ];
    }
    
    public function attr()
    {
        return $this->hasOne(SysCardAttr::class,'id','attr_id');
    }
 

}