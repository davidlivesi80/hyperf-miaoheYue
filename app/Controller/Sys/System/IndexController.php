<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller\Sys\System;

use App\Common\Model\Users\UserBalance;
use App\Common\Model\Users\UserRelation;
use App\Common\Model\Users\UserSecondIncome;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserCardsService;
use App\Common\Service\Users\UserExchangeService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserSafetyService;
use App\Common\Service\Users\UserSecondIncomeService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserWithdrawService;
use App\Common\Service\Users\UserBalanceService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserService;

class IndexController extends BaseController
{

    public function index()
    {
        $adminId = $this->request->input('adminId');

        $meuns  = $this->powerService->getMenus($adminId);

        return $this->success('请求成功',$meuns);

    }

    public function logout()
    {
        if(!$this->jwt->logout()){
            return $this->fials('退出失败');
        }

        return $this->success('退出成功');

    }

    public function change()
    {

        return $this->success('修改成功');

    }

    public function personal()
    {
        return $this->success('获取成功');
    }

    public function clear()
    {
        return $this->success('清除成功');
    }

    /*会员统计*/
    public function countsUser()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }
        //伞下会员
         $total_number = $this->app(UserService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
             $query->where('created_at',">=",$startTime)->where('created_at',"<",$endTime);
        })->where('types','<>',3)->count();
        //体验会员
        $found_number = $this->app(UserService::class)->getQuery()->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            $query->where('created_at',">=",$startTime)->where('created_at',"<",$endTime);
        })->where('types',3)->count();
        //有效会员
        $xiaos_number = $this->app(UserCountService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id', function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('last_time','>=',0)->count();
        //有效IP
        $local_number = $this->app(UserService::class)->getQuery()->where('types','<>',3)->distinct('login_ip')->count('login_ip');
        //活跃人数
        $active_number = Db::table('user')->when($parent, function ($query) use($parent){
            return $query->whereIn('id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereDate('login_time',date('Y-m-d'))->where('types','<>',3)->count();
        //充值人数
        $recharge_number = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('updated_at','>=' ,$startTime)->where('updated_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id', function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn("order_type",[3,4])->distinct('user_id')->count('user_id');
        //今日首充人数
        $recharge_onenum = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        ->whereDate('recharge_at',date('Y-m-d'))
        ->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id', function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn("order_type",[3,4])->distinct('user_id')->count('user_id');
        return $this->success('请求成功',compact('total_number','found_number','xiaos_number','local_number','active_number','recharge_number','recharge_onenum'));
    }

    /*投资统计*/
    public function countsRobot()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $startTimestamp = $startTime ? strtotime($startTime) : "";
        $endTimestamp = $endTime ? strtotime($endTime) : "";
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }
        $count_user_ids = explode('@',$this->app(SysConfigService::class)->value('count_user_ids'));
        // 投资总额
        $second_total =  Db::table('user_second')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->whereNotIn('user_id',$count_user_ids)->sum('num');
        // 盈利总额
        $second_income =  Db::table('user_second_income')->when( $startTimestamp != '' && $endTimestamp != '', function ($query) use($startTimestamp,$endTimestamp){
            return $query->where('reward_time','>=' ,$startTimestamp)->where('reward_time',"<",$endTimestamp);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->whereNotIn('user_id',$count_user_ids)->where('reward_type',1)->sum('reward');
        // 亏损总额
        $second_deficit =  Db::table('user_second_income')->when( $startTimestamp != '' && $endTimestamp != '', function ($query) use($startTimestamp,$endTimestamp){
            return $query->where('reward_time','>=' ,$startTimestamp)->where('reward_time',"<",$endTimestamp);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->whereNotIn('user_id',$count_user_ids)->where('reward_type',2)->sum('reward');

        // 赔付总额
        $second_safety =  Db::table('user_safety_order')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->whereNotIn('user_id',$count_user_ids)->sum('total');

        //其他场次（非带单盈亏）
        $income_other =  Db::table('user_second')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->where('scene',3)->where('profit_status',1)->sum('profit');

        $deficit_other =  Db::table('user_second')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('user_id',function ($query){
            return $query->select('user_id')->from('user_count')->where(function ($query){
                return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
            });
        })->where('scene',3)->where('profit_status',2)->whereNotIn('user_id',$count_user_ids)->sum('profit');

        // 盈亏总额
        $second_surplus = bcsub( bcadd(strval($second_income),strval($second_safety),6) ,strval($second_deficit),6);

        $other_surplus = bcsub( bcadd(strval($income_other),strval("0"),6) ,strval($deficit_other),6);

        return $this->success('请求成功',compact('second_total','second_income','second_deficit','second_safety','second_surplus','other_surplus'));
    }
    /*竞猜统计*/
    public function countsLottery()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $startTimestamp = $startTime ? strtotime($startTime) : "";
        $endTimestamp = $endTime ? strtotime($endTime) : "";
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }

        // 投资总额
        $lottery_total =  Db::table('user_lottery')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereNotIn('user_id',function ($query){
            return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
        })->sum('num');
        // 盈利总额
        $lottery_income =  Db::table('user_lottery_income')->when( $startTimestamp != '' && $endTimestamp != '', function ($query) use($startTimestamp,$endTimestamp){
            return $query->where('reward_time','>=' ,$startTimestamp)->where('reward_time',"<",$endTimestamp);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereNotIn('user_id',function ($query){
            return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
        })->where('reward_type',1)->sum('reward');
        // 亏损总额
        $lottery_deficit =  Db::table('user_lottery_income')->when( $startTimestamp != '' && $endTimestamp != '', function ($query) use($startTimestamp,$endTimestamp){
            return $query->where('reward_time','>=' ,$startTimestamp)->where('reward_time',"<",$endTimestamp);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereNotIn('user_id',function ($query){
            return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
        })->where('reward_type',2)->sum('reward');

        // 盈亏总额
        $lottery_surplus = bcsub( strval($lottery_income) ,strval($lottery_deficit),6);

        return $this->success('请求成功',compact('lottery_total','lottery_income','lottery_deficit','lottery_surplus'));
    }

    /*出入金统计*/
    public function countsWithdraw()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }

        // 已通过
        $data['recharge_usdc'] = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn("order_type",[1,2])->sum('order_mone');//系统

        $data['recharge_usdt'] = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn("order_type",[3])->whereNotIn('user_id',[818,831])->sum('order_mone');//线上 - bsc

        $data['recharge_usde'] = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn("order_type",[4])->sum('order_mone');//线上-trc

        $data['withdraw_usdt'] = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->where("order_key",0)->whereNotIn('user_id',[818,831])->sum('order_mone');//自动

        $data['withdraw_usdc'] = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
        ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->where("order_key",1)->whereNotIn('user_id',[818,831])->sum('order_mone');//手动
        //date("Y-m-d",strtotime($startTime))  == "2025-01-10"

        //冲提差
        $data['recharge_deposit'] = bcsub( bcadd(strval($data['recharge_usdt']),strval($data['recharge_usde']),6) ,bcadd(strval($data['withdraw_usdt']),strval($data['withdraw_usdc']),6) ,6);

        //bsc提现钱包余额
        $data['bsc_withdraw'] = 0;
        //trx提现钱包余额
        $data['trx_withdraw'] = 0;

        return $this->success('请求成功', $data);
    }

    /*保险统计*/
    public function countsSafety()
    {
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }

        $total = $this->app(UserSafetyService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('total');

        $user_num= $this->app(UserExtendService::class)->getQuery()->where('last_safety','>=',time())->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('safety_id','>',0)->count();

        return $this->success('请求成功',compact('total','user_num'));
    }
    /*资产统计*/
    public function countsBalance()
    {
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }
        //全网余额  - 排除测试
        $user_usdt = $this->app(UserBalanceService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('usdt');
        //未被限制提现用余额 - 排除测试
        $user_usdc = $this->app(UserBalanceService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereIn('user_id',function ($query) use($parent){
            return $query->select('user_id')->from('user_extend')->where('is_withdraw',1)->where('is_autodraw',1);
        })->whereNotIn('user_id',function ($query) use($parent){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('usdt');

        return $this->success('请求成功',compact('user_usdt','user_usdc'));
    }

    /*收益统计*/
    public function countsReward()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }
        //流水动态
        $robot_pnamic = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('reward_type',1)->where('settle_time',0)->sum('total');
        //流水动态
        $robot_dnamic = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('reward_type',1)->where('settle_time','>',0)->sum('reward');

        //流水团队-待结算-只支持某人-上一周
        if($parent){
            list($orderTotal,$start,$ends,$detailes) = $this->app(UserSecondService::class)->groupsCompute($parent->id);
            $robot_groubs = $orderTotal;
        }else{
            $robot_groubs = Db::table('user_second_income')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
            })->when($parent, function ($query) use($parent){
                return $query->whereIn('user_id', function ($query) use($parent){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
                });
            })->whereNotIn('user_id',function ($query) {
                return $query->select('id')->from('user')->where('types',3);
            })->where('groups_time',0)->sum('total');
        }

        $groupRate = $this->app(SysConfigService::class)->value('groups_rate');
        $robot_groubs = bcmul(strval($robot_groubs),strval($groupRate/100),6);

        //流水团队-已结算
        $robot_groups = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->where('user_id', $parent->id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('reward_type',2)->sum('reward');


        return $this->success('请求成功',compact('robot_pnamic','robot_dnamic','robot_groubs','robot_groups'));
    }

    /*存取差值 + 净新增值*/
    public function countsSurplus()
    {
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $parent = "";
        $dateUser = $this->request->input('dateUser','');
        $topId = $this->request->input('topId','');
        if($topId){
            $parent = Db::table('user')->where('user.id',intval($topId))->first();
        }elseif($dateUser ){
            $parent = Db::table('user')->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
        }

        //一星会员
        $level_1_num = count($this->app(UserExtendService::class)->getQuery()->where('level',1)->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->pluck('user_id')->toArray());
        //二星会员
        $level_2_num = count($this->app(UserExtendService::class)->getQuery()->where('level',2)->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->pluck('user_id')->toArray());
        //三星会员
        $level_3_num = count($this->app(UserExtendService::class)->getQuery()->where('level',3)->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->pluck('user_id')->toArray());

        return $this->success('清除成功',compact('level_1_num','level_2_num','level_3_num'));
    }


    /**
     * 系统报表
     */
    public function reportSys()
    {
        $where= $this->request->inputs(['username']);

        $perPage = $this->request->input('limit',10);

        $page = $this->request->input('page',1);

        $lists = [];$this->app(UserCountService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }


    public function job()
    {


    }

}
