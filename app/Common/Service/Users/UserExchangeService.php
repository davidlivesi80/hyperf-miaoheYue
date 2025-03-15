<?php


namespace App\Common\Service\Users;


use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserExchangeLogic;
use Upp\Exceptions\AppException;


class UserExchangeService extends BaseService
{
    /**
     * @var UserExchangeLogic
     */
    public function __construct(UserExchangeLogic $logic)
    {
        $this->logic = $logic;
    }


    /*计算*/
    public function compute($power,$number){

        $total = bcmul((string)$power->price,(string)$number,6);

        $rate = bcmul((string)$power->rate,(string)$number,6);

        $mone = bcsub((string)$total,'0',6);

        return compact("total","rate",'mone');
    }

    /**

     * 添加

     */

    public function create($userId,$exchange,$number){

        $exists = $this->logic->getQuery()->where('user_id',$userId)->where('order_paid',0)->exists();

        if ($exists) {
            throw new AppException('order_tip',400);//您有一笔订单,等待处理
        }

        if(!$this->app(UserExtendService::class)->findByUid($userId)['is_withdraw']){
            throw new AppException('withdraw_auto_on',400);//提现权未开启，请联系管理员
        }
        
        if($number > $exchange->max_num || $number < $exchange->min_num){
        	throw new AppException('exchange_num',400);
        }
        $compute = $this->compute($exchange,$number);
        if( 0>=$compute['total'] || 0>=$compute['mone']){
            throw new AppException('exchange_calculation_error',400);
        }
        //检测余额
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($number) > $balance[strtolower($exchange['give_coin'])]){
            throw new AppException('insufficient_balance',400);
        }
        //手续检查
        if(abs( $compute['rate']) > $balance['red']){
            throw new AppException('insufficient_formalities',400);
        }

        //订单数据
        $_order['order_sn']   =  $this->makeOrdersn('EX');
        $_order['user_id']   =  $userId;
        $_order['exchange_id']   =  $exchange->id;
        $_order['order_give_coin']   = strtolower($exchange['give_coin']);
        $_order['order_give_number']   = $number;
        $_order['order_paid_coin']   = strtolower($exchange['paid_coin']);
        $_order['order_paid_number']   = $compute['total'];
        $_order['order_amount']   = $compute['mone'];
        $_order['order_rate_number'] = $compute['rate'];
        $_order['order_paid']   = 1;

        //执行流程
        Db::beginTransaction();

        try {

            //创建订单
            $order = $this->logic->create($_order);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //扣除兑换
            $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_give_coin,$balance[$order->order_give_coin],-$order->order_give_number,5,'在线兑换',$order->order_id);
            if($res !== true){
                throw new \Exception('兑换失败');
            }
            //扣除红砖手续
            $reb = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,'red',$balance['red'],-$order->order_rate_number,5,'在线兑换',$order->order_id);
            if($reb !== true){
                throw new \Exception('手续失败');
            }

            //增加余额
            $rel =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_paid_coin,$balance[$order->order_paid_coin],$order->order_amount,5,'在线兑换',$order->order_id);
            if($rel !== true){
                throw new \Exception('支付失败');
            }
            
            //更新提现权益
            $this->app(UserCountService::class)->getQuery()->where('user_id',$order->user_id)->update(['income_auth'=> strtotime(date("Y-m-d")) + 86400]);
            $this->app(UserRobotService::class)->getQuery()->where('user_id',$order->user_id)->update(['income_auth'=> strtotime(date("Y-m-d")) + 86400]);

            Db::commit();
            return $order;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[兑换交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }


    public function cancel($userId,$id){

        $order = $this->logic->getQuery()->when($userId, function ($query) use($userId){

            return $query->where('user_id', $userId);

        })->where('order_id',$id)->where('order_paid',0)->first();

        if (!$order) {
            throw new AppException('订单不可操作',400);
        }

        Db::transaction(function () use ($order){
            //取消订单
            try {
                $this->logic->remove($order['order_id']);
            } catch (\Throwable $e) {
                throw new AppException('删除失败',400);
            }
        });

        return true;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);
        

        return $list;
    }

    /**
     * 查询构造
     */
    public function searchApi($userId, $page=1,$perPage = 10){

        $list = $this->logic->search(['user_id'=>$userId])->paginate($perPage,['*'],'page',$page);

        return $list;

    }



}