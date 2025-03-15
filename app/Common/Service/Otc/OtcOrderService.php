<?php


namespace App\Common\Service\Otc;


use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserExtendService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Otc\OtcOrderLogic;
use Upp\Exceptions\AppException;

class OtcOrderService extends BaseService
{
    /**
     * @var OtcOrderLogic
     */
    public function __construct(OtcOrderLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['buyer:id,username','seller:id,username','market:id,order_sn'])->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where,$page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','target:id,username'])->paginate($perPage,['*'],'page',$page);;

        return $list;
    }


    /**
     * 摘单下单
     */
    //发布
    function poplish($userId,$data){
        $poplish_lock =  $this->getCache()->get('poplish_lock_'.$data['market_id']);
        if($poplish_lock){
            throw new AppException('操作频繁，稍后尝试！',400);
        }
        $this->getCache()->set('poplish_lock_'.$data['market_id'],$data['market_id'],2);
        // 该用户提现状态
        if(!$this->app(UserExtendService::class)->findByUid($userId)['is_withdraw']){
            throw new AppException('提现权未开启，请联系管理员',400);
        }

        $publish = $this->app(OtcMarketService::class)->getQuery()->where(['id'=>$data['market_id'],'finish_time'=>0])->first();
        if (!$publish) {
            throw new AppException('该广告不存在',400);
        }
        if ($publish->status != 1) {
            throw new AppException('该广告已下线',400);
        }
        if ($publish->user_id == $userId) {
            throw new AppException('该广告不可下单',400);
        }
        $number = $publish['order_nums'];
        if ($number > $publish->max_num) {
            throw new AppException('该广告最大下单为:'. $publish->max_num,400);
        }
        if ($number < $publish->min_num) {
            throw new AppException('该广告最小下单为:'. $publish->min_num,400);
        }
        //余额
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        $rate = $this->app(OtcCoinsService::class)->find($publish->otc_coin_id)['rate'];
        $order_amount = $this->cus_floatval($publish['order_amount'],6);
        //余额
        if(abs($order_amount) > $balance[strtolower($data['pay_coin'])]){
            throw new AppException('余额不足',400);
        }
        $total_amount = bcmul((string)$order_amount,(string)$rate,6);
        $total_rate = bcdiv((string)$total_amount,'100',6);
        $total_amount = bcsub((string)$total_amount,(string)$total_rate,6);

        Db::beginTransaction();
        try {
            $orderData = [
                'market_id'  => $data['market_id'],
                'seller_uid' => $publish->side == 2 ? $publish->user_id : $userId,
                'buyer_uid'  => $publish->side == 2 ? $userId : $publish->user_id,
                'other_uid'  => $publish->user_id,
                'users_uid'  => $userId,
                'side'       => $publish->side == 2 ? 1 : 2,
                'otc_coin_name'  => $publish->otc_coin_name,
                'otc_coin_id'    => $publish->otc_coin_id,
                'number'     => $number,
                'price'      => $publish->price,
                'pay_coin'   => strtolower($data['pay_coin']),
                'total_amount' => $total_amount,
                'total_price'=> $order_amount,
                'order_time' => date('Y-m-d H:i:s'),
                'pay_time'   => date('Y-m-d H:i:s'),
                'deal_time'  => date('Y-m-d H:i:s'),
                'status'     => 1
            ];
            //提交匹配
            $order = $this->logic->create($orderData);
            if(!$order){
                throw new \Exception('创建失败');
            }

            //更新发布
            $rasult = $this->app(OtcMarketService::class)->getQuery()->where(['id'=>$publish->id,'finish_time'=>0])->update(['finish_time'=>time()]);
            if (!$rasult) {
                throw new \Exception("稍后尝试");
            }

            //自己扣除余额
            $res =  $this->app(UserBalanceService::class)->rechargeTo($order->users_uid,$order->pay_coin,$balance[$order->pay_coin],-$order->total_price,21,'OTC摘单扣除',$order->id);
            if($res !== true){
                throw new \Exception('资产扣除余额失败');
            }
            //自己增加代币
            $rea =  $this->app(UserBalanceService::class)->rechargeTo($order->users_uid,$order->otc_coin_name,$balance[$order->otc_coin_name],$order->number,22,'OTC配发代币',$order->id);
            if($rea !== true){
                throw new \Exception('资产增加代币失败');
            }
            //对方增加USDT、扣除手续
            $balanceOther = $this->app(UserBalanceService::class)->findByUid($order->other_uid);
            $rel =  $this->app(UserBalanceService::class)->rechargeTo($order->other_uid,$order->pay_coin,$balanceOther[$order->pay_coin],$order->total_amount,23,'OTC结算金额',$order->id);
            if($rel !== true){
                throw new \Exception('资产失败');
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[OTC交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }





}