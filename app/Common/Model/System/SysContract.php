<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;


class SysContract extends BaseModel
{


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
        return 'sys_contract';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'contract_title','contract_name','contract_address','contract_abi'
        ];
    }
   

}