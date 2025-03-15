<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Upp\Basic\BaseModel;


class SysStock extends BaseModel
{
    
    protected $appends  = ['card_no','power'];
     
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
        return 'sys_stock';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'token_id'
        ];
    }
    
    /**
     * @return string
     
    public function getOwnerOfAttribute()
    {
        
        $token = Di(SysStockService::class)->initCard();
        
        $owner = $token->ownerOf($this->token_id);
        
        return $owner;

    }*/
    
    public function card()
    {
        return $this->hasOne(SysCards::class,'id','card_id');
    }
    

    
    public function getCardNoAttribute()
    {
        return  substr_replace((string)$this->token_id,'****',4,10);//substr_replace(md5((string)$this->token_id),'****','6','22');
    }
    
    public function getPowerAttribute()
    {
        if($this->card){
            $power = $this->card->power_now;
            return $power;
        }else{
            return 0;
        }
    }

    
  
}