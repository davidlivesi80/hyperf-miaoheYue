<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysExchangeLogic;

class SysExchangeService extends BaseService
{

    /**
     * @var SysExchangeLogic
     */
    public function __construct(SysExchangeLogic $logic)
    {
        $this->logic = $logic;
    }

    public function check($give_coin,$paid_coin){

        $info = $this->logic->getQuery()->where('paid_coin',$paid_coin)->where('give_coin',$give_coin)->first();

        return $info;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->with(['give:id,coin_name,coin_symbol,image','paid:id,coin_name,coin_symbol,image'])->get();


        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchOne($id){

        $list = $this->logic->getQuery()->with(['give:id,coin_name,coin_symbol,image','paid:id,coin_name,coin_symbol,image'])->find($id);


        return $list;
    }



}