<?php


namespace App\Common\Service\Users;


use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserLeaderLogic;


class UserLeaderService extends BaseService
{
    /**
     * @var UserLeaderLogic
     */
    public function __construct(UserLeaderLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10,$sort="user_id",$order="asc"){
        $list = $this->logic->search($where)->with('user:id,username,email,mobile,is_bind')->whereDate('created_at',date("Y-m-d"))->orderBy($sort, $order)->paginate($perPage,['*'],'page',$page);
        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with('user:id,username,email,mobile,is_bind')->get();
        return $list;

    }

    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }

    /**
     * 查询构造
     */
    public function create($userId){
        //清除数据
        $this->logic->getQuery()->where('user_id',$userId)->whereDate('created_at',date('Y-m-d'))->delete();
        //今日奖金
        $yestoday= Carbon::yesterday();
        $start = date('Y-m-d H:i:s', $yestoday->startOfDay()->timestamp);
        $ends  = date('Y-m-d H:i:s', $yestoday->endOfDay()->timestamp);
        $record['user_id'] = $userId;
        //累计充值
        $record['recharge'] =  $this->cus_floatval(   Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
            ->whereIn('user_id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->whereIn("order_type",[3,4])->sum('order_mone') );//线上-trc-bsc;
        //昨日充值
        $record['recharge_today'] = $this->cus_floatval(   Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
            ->where('created_at','>=' ,$start)->where('created_at',"<=",$ends)
            ->whereIn('user_id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->whereIn("order_type",[3,4])->sum('order_mone') );//线上 - bsc
        //累计提现
        $record['withdraw'] = $this->cus_floatval(   Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
            ->whereIn('user_id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->sum('order_mone')  );
        //昨日提现
        $record['withdraw_today']  = $this->cus_floatval(   Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
            ->where('created_at','>=' ,$start)->where('created_at',"<=",$ends)
            ->whereIn('user_id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->sum('order_mone') );
        //净入金
        $record['deposit'] =  bcsub( strval($record['recharge'])  ,strval($record['withdraw']) ,2);
        //伞下余额
        $record['balance'] =  $this->app(UserBalanceService::class)->getQuery()->whereIn('user_id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->sum('usdt');
        //伞下注册
        $record['regis_total'] =  $this->app(UserService::class)->getQuery()->whereIn('id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->where('types','<>',3)->count();
        //昨日注册
        $record['regis_today'] =  $this->app(UserService::class)->getQuery()->whereIn('id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->where('created_at',">=",$start)->where('created_at',"<=",$ends)->where('types','<>',3)->count();
        //伞下有效
        $record['user_xiao'] =  $this->app(UserCountService::class)->getQuery()->whereIn('id', function ($query) use($userId){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
            })->whereNotIn('user_id', function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('last_time','>=',0)->count();
        //数据时间
        $record['date_at'] = $start;
        //创建
        $order = $this->logic->create($record);
        if(!$order){
            return false;
        }
        return true;
    }


}