<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysCoins extends BaseModel
{
    use SoftDeletes;
    /**
     * 关闭时间错
     */
    public $timestamps = false;

    /**
     * 自定义属性
     */
    protected $appends  = ['nets'];

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
        return 'sys_coins';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'net_id','coin_type','coin_symbol','coin_name','image','address','usd','sort','withdots_rate','recharge_rate','transfer_rate','withdots_min_max'
            ,'recharge_min_max','transfer_min_max','withdots_on_off','recharge_on_off','transfer_on_off','withdots_remark','recharge_remark','transfer_remark'
            ,'withdots_num'
        ];
    }
    /**
     * 多对多建立关系
     */
    public function getNetsAttribute()
    {
        $nets = [];
        if($this->net_id){
            $netIds = explode(',',$this->net_id);
            for ($i=0; $i<count($netIds);$i++){
                if($netIds[$i] == 3){
                    $nets[] = ['id'=>3,'name'=>'BNB'];
                }
                if($netIds[$i] == 5){
                    $nets[] = ['id'=>5,'name'=>'ETH'];
                }
                if($netIds[$i] == 4){
                    $nets[] = ['id'=>4,'name'=>'TRC'];
                }
                if($netIds[$i] == 6){
                    $nets[] = ['id'=>6,'name'=>'OP'];
                }
            }
        }
        return $nets;
    }


}