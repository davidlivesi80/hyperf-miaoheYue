<?php


namespace App\Common\Service\System;


use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\System\SysDataLeadLogic;

class SysDataLeadService extends BaseService
{
    /**
     * @var SysDataLeadLogic
     */
    public function __construct(SysDataLeadLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        $list->each(function ($parent){

            $recharge_usdt_num = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent['user_id']);
            })->sum('order_mone');

            $withdraw_usdt_num = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent['user_id']);
            })->sum('order_mone');

            $withdraw_wld_num = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','wld')->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent['user_id']);
            })->sum('order_mone');

            $withdraw_wld_money = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','wld')->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent['user_id']);
            })->sum('order_total');
            $parent['recharge_usdt_num'] = $recharge_usdt_num;
            $parent['withdraw_usdt_num'] = $withdraw_usdt_num;
            $parent['withdraw_wld_num'] =  $withdraw_wld_num;
            $parent['withdraw_wld_money'] = $withdraw_wld_money;
            $parent['deposit_num']  = bcsub((string)$recharge_usdt_num,bcadd((string)$withdraw_usdt_num,(string)$withdraw_wld_money,6),6);

            $parent['withdraw_recharge_usdt']   = bcmul(bcdiv((string)$withdraw_usdt_num,(string)$recharge_usdt_num,4),'100',4);

            return $parent;
        });

        return $list;
    }


}