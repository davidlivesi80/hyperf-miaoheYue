<?php

namespace App\Common\Service\Users;


use App\Common\Model\Users\UserSafety;
use App\Common\Service\System\SysSafetyService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSafetyOrderLogic;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserSafetyOrderService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserSafetyOrderLogic
     */
    public function __construct(UserSafetyOrderLogic $logic)
    {
        $this->logic = $logic;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $with = [], $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with($with)->paginate($perPage, ['*'], 'page', $page);

        return $list;

    }


    /**
     * 按用户，按时间区间赔付普通场次亏损订单
     */
    public function create($userExtend,$scene,$start,$ends,$dan5start,$dan5end)
    {
        $usecond_ids = $this->app(UserSecondService::class)->getQuery()->where('user_id',$userExtend->user_id)->where('scene',$scene)->where('profit_status',2)->whereBetween('created_at',[$start,$ends])->pluck('id')->toArray();
        $usecond_ids_win = $this->app(UserSecondService::class)->getQuery()->where('user_id',$userExtend->user_id)->where('scene',$scene)->where('profit_status',1)->whereBetween('created_at',[$start,$ends])->pluck('id')->toArray();
        if(0 >= count($usecond_ids)){
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$userExtend->user_id)->update(['safety_time'=>time()]);
            return true;
        }
        //如果已赔付不陪
        if(time() > $userExtend->last_safety){
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$userExtend->user_id)->update(['safety_time'=>time()]);
            $this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->update(['is_safety'=>-1]);
            return true;
        }
        //如果第5单不买不陪
        $dan5count = $this->app(UserSecondService::class)->getQuery()->where('user_id',$userExtend->user_id)->where('scene',$scene)->whereBetween('created_at',[$dan5start,$dan5end])->count();
        if(0 >= $dan5count){
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$userExtend->user_id)->update(['safety_time'=>time()]);
            $this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->update(['is_safety'=>-2]);
            return true;
        }
        ///如果该保险类型不存在不配
        $safety = $this->app(SysSafetyService::class)->searchApi($userExtend->safety_id);
        if (empty($safety) || $safety['status'] == 0) {
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$userExtend->user_id)->update(['safety_time'=>time()]);
            $this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->update(['is_safety'=>-1]);
            return true;
        }
        //查询本次赔付订单额
        if($scene = 2){
            $amount = $this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->sum('profit');
        }else{
            $amount = $this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->sum('profit');
        }
        // 查看最大赔付金
        if($amount >= $safety['title']){
            $total = bcmul('1', $safety['title'],6);
        }else{
            $total = $amount;
        }
        $balance = $this->app(UserBalanceService::class)->findByUid($userExtend->user_id);
        //减去盈利的钱数
        $amount_win = abs($this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids_win)->sum('profit'));
        $total = bcsub((string)$total,(string)$amount_win,6);
        $error['user_id'] = $userExtend->user_id;
        $error['amount'] = $amount;
        $error['total'] = $total;
        $error['amount_win'] = $amount_win;
        $this->logger('[跟单赔付]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));

        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userExtend->user_id,
                'order_sn' => $this->makeOrdersn('SO'),
                'usecond_id' => implode(',',$usecond_ids),
                'safety_id' => $safety['id'],
                'amount' => $amount,
                "total" => $total,
                'status' => 1,
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //赔付
            $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $order->total, 16, '保险赔付', $order->id);
            if ($res !== true) {
                throw new \Exception('Asset failed');//资产失败
            }
            if($this->app(UserSecondService::class)->getQuery()->whereIn('id',$usecond_ids)->update(['is_safety'=>$order->id]) === false){
                throw new \Exception('赔付失败');//资产失败
            }
            if($this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_time'=>time()]) === false){
                throw new \Exception('赔付失败');//资产失败
            }
            Db::commit();
            //更新赔付统计
            $safety_total = $this->logic->getQuery()->where('user_id',$order->user_id)->sum('total');
            if($safety_total > 0){
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety'=>$safety_total]);
            }
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[跟单赔付]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 按用户ID  手动赔付
     */
    public function found($userExtend,$amount,$num)
    {



        $balance = $this->app(UserBalanceService::class)->findByUid($userExtend->user_id);
        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userExtend->user_id,
                'order_sn' => $this->makeOrdersn('SO'),
                'usecond_id' => "",
                'safety_id' => 0,
                'amount' => $amount,
                "total" => $amount,
                'status' => 1,
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //赔付
            $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $order->total, 17, '亏损赔付', $order->id);
            if ($res !== true) {
                throw new \Exception('Asset failed');//资产失败
            }
            Db::commit();
            //更新赔付统计
            $safety_total = $this->logic->getQuery()->where('user_id',$order->user_id)->sum('total');
            if($safety_total > 0){
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety'=>$safety_total]);
            }
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[手动赔付]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


    /**
     * 设置赔付时间区间
     */
    public function sets($scene,$start,$ends,$dan5_start,$dan5_end)
    {

        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if (!$powerInsert){
            throw new AppException('请00:05分后尝试',400);//场次不存在，其实就是没K线数据
        }
        Db::beginTransaction();
        try {
            //设置时间区间
            if( Db::table('sys_power')->where('id',$powerInsert->id)->update(['safety_start'=>$start,'safety_ends'=>$ends,'safety_scene'=>$scene,'dan5_start'=>$dan5_start,'dan5_end'=>$dan5_end]) === false){
                throw new \Exception('设置时间区间失败');//资产失败
            }
            //清除用户时间
            if($this->app(UserExtendService::class)->getQuery()->where('safety_time','>',0)->update(['safety_time'=>0]) === false){
                throw new \Exception('清除用户时间失败');//资产失败
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[设置赔付]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


    /**
     * 统计赔付
     */
    public function counts(array $where)
    {
        $total = $this->logic->getQuery()->where('status',1)->sum('total');//累计赔付
        $today_amount = $this->logic->search($where)->where('status',1)->sum('amount');//应赔付
        $today_total = $this->logic->search($where)->where('status',1)->sum('total');//真实赔付
        $safety_total = $this->app(UserSafetyService::class)->getQuery()->where('status',1)->sum('total');//保险总额
        return compact('total','today_amount','today_total','safety_total');

    }


}