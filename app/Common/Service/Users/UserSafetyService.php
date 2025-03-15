<?php

namespace App\Common\Service\Users;


use App\Common\Service\System\SysSafetyService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSafetyLogic;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserSafetyService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserSafetyLogic
     */
    public function __construct(UserSafetyLogic $logic)
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
     * 添加
     */
    public function create($userId, $safetyId)
    {
        //查看交易对状态
        $safety = $this->app(SysSafetyService::class)->searchApi($safetyId);
        if (empty($safety) || $safety['status'] == 0) {
            throw new AppException('safety_card_error', 400);//保险不存在
        }
        $userExtend = $this->app(UserExtendService::class)->findByUid($userId);
        if(time() < $userExtend->last_safety){
            throw new AppException('safety_last_error', 400);//保险未到期
        }
        // 查看是否充足
        $total = bcmul('1', $safety['price'],6);
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if (abs($total) > $balance['usdt']) {
            throw new AppException('insufficient_balance', 400);//余额不足
        }

        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userId,
                'order_sn' => $this->makeOrdersn('ST'),
                'safety_id' => $safety['id'],
                'price' => $safety['price'],
                "total" => $total,
                "order_type"=>0,//0usdt购买，1保险卷购买
                'period' => $safety['period'],//周期30
                'buy_time' => date("Y-m-d H:i:s"),
                'pay_time' => date("Y-m-d H:i:s"),
                'status' => 1,
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //扣除余额
            $subNum = bcmul((string)$order->total, '-1', 6);
            $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $subNum, 15, '购买保险', $order->id);
            if ($res !== true) {
                throw new \Exception('Asset failed');//资产失败
            }
            //更新时间
            $last_safety = strtotime(date('Y-m-d 23:59:59',intval(bcadd((string)time(),(string)($order->period * 86400)))));
            $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_id'=>$order->safety_id,"last_safety"=>$last_safety]);
            if(!$rel){
                throw new \Exception('更新失败');
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[保险下单]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*保险卷*/
    public function coupons($userId, $safetyId)
    {

        //查看交易对状态
        $safety = $this->app(SysSafetyService::class)->searchApi($safetyId);
        if (empty($safety) || $safety['status'] == 0) {
            throw new AppException('safety_card_error', 400);//保险不存在
        }
        $userExtend = $this->app(UserExtendService::class)->findByUid($userId);
        if(time() < $userExtend->last_safety){
            throw new AppException('safety_last_error', 400);//保险未到期
        }
        // 查看保险卷
        $total = bcmul('1', (string)$safety['price'],6);
        if($safety['title'] == 300){
            $number = 1;
        }else{
            $number = bcdiv((string)$safety['title'],'1500',0);
        }
        if(0 >= $number){
            throw new AppException('conpons_balance', 400);//保险卷不足
        }
        $couponsList = $this->app(UserSafetyCouponsService::class)->getQuery()->where('status',0)->where('user_id',$userId)->pluck('id')->toArray();
        if (abs($number) > count($couponsList)) {
            throw new AppException('conpons_balance', 400);//保险卷不足
        }
        $couponsIds = array_slice($couponsList,0,$number);
        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userId,
                'order_sn' => $this->makeOrdersn('ST'),
                'safety_id' => $safety['id'],
                'price' => $safety['price'],
                "total" => $total,
                "order_type"=>1,//0usdt购买，1保险卷购买
                'period' => $safety['period'],//周期30
                'buy_time' => date("Y-m-d H:i:s"),
                'pay_time' => date("Y-m-d H:i:s"),
                'status' => 1,
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //更新卷数据
            if(!$this->app(UserSafetyCouponsService::class)->getQuery()->whereIn('id',$couponsIds)->update(['status'=>1])){
                throw new \Exception('抵扣失败');
            }
            //更新时间
            $last_safety = strtotime(date('Y-m-d 23:59:59',intval(bcadd((string)time(),(string)($order->period * 86400)))));
            $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_id'=>$order->safety_id,"last_safety"=>$last_safety]);
            if(!$rel){
                throw new \Exception('更新失败');
            }
            Db::commit();
            //统计接收者有效数量
            $safety_coupons = $this->app(UserSafetyCouponsService::class)->getQuery()->where('user_id',$order->user_id)->where('status',0)->sum('number');
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_coupons'=>$safety_coupons]);
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[保险卷下单]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 添加-
     */
    public function found($userId, $safetyId)
    {

        //查看交易对状态
        $safety = $this->app(SysSafetyService::class)->searchApi($safetyId);
        if (empty($safety) || $safety['status'] == 0) {
            throw new AppException('safety_card_error', 400);//保险不存在
        }
        $userExtend = $this->app(UserExtendService::class)->findByUid($userId);
        if(time() < $userExtend->last_safety){
            throw new AppException('safety_last_error', 400);//保险未到期
        }
        // 查看是否充足
        $total = bcmul('1', $safety['price'],6);
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);

        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userId,
                'order_sn' => $this->makeOrdersn('ST'),
                'safety_id' => $safety['id'],
                'price' => $safety['price'],
                "total" => $total,
                "order_type"=>0,
                'period' => $safety['period'],//周期30
                'buy_time' => date("Y-m-d H:i:s"),
                'pay_time' => date("Y-m-d H:i:s"),
                'status' => 1,
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //更新时间
            $last_safety = strtotime(date('Y-m-d 23:59:59',intval(bcadd((string)time(),(string)($order->period * 86400)))));
            $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_id'=>$order->safety_id,"last_safety"=>$last_safety]);
            if(!$rel){
                throw new \Exception('更新失败');
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[保险下单]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 取消
     */
    public function cancel($order,$isReurn=0)
    {

        // 查看是否充足
        $total = bcmul('1', (string)$order->price,6);
        $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);

        Db::beginTransaction();
        try {
            //创建
            $ores = $this->logic->getQuery()->where('id',$order->id)->delete();
            if(!$ores){
                throw new \Exception('删除失败');
            }
            if($isReurn > 0){
                //扣除余额
                $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $total, 1, '保险退还', $order->id);
                if ($res !== true) {
                    throw new \Exception('Asset failed');//资产失败
                }
            }
            //更新时间
            $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_id'=>0,"last_safety"=>0]);
            if(!$rel){
                throw new \Exception('更新失败');
            }
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[保险取消]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }



}