<?php


namespace App\Common\Service\Users;

use App\Common\Model\Users\UserReward;
use App\Common\Service\System\SysCardsService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysContractService;
use App\Common\Service\System\SysRobotService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRadotLogic;
use Upp\Exceptions\AppException;
use Upp\Service\BnbService;
use Upp\Service\SignService;
use Upp\Traits\HelpTrait;

class UserRadotService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserRadotLogic
     */
    public function __construct(UserRadotLogic $logic)
    {
        $this->logic = $logic;

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
    public function searchExp(array $where){

        $list = $this->logic->search($where)->with('user:id,username')->get();

        return $list;

    }

    /**
     * 添加
     */
    public function found($userId,$totalNum,$paySeries,$payType){
        $lastTime = mktime(23,50,0,date('m'),date('d'),date('Y'));
        if(time() >= $lastTime){
           throw new AppException('Wrong_power_try_later',400);
        }
        $pools = $this->app(SysRobotService::class)->find(2);
        if(!$pools){
            throw new AppException('pools_wrong',400);
        }
        if ($pools->price > $totalNum) {
            throw new AppException('amount_is_too_small',400);//投资金额太少
        }
        $coin_wld = $this->app(SysCoinsService::class)->findWhere('coin_symbol','wld');
        $coin_atm = $this->app(SysCoinsService::class)->findWhere('coin_symbol','atm');
        $total_oso = 0;$total=0;
        if($payType == 3){
            $total = bcmul((string)$totalNum, (string)$coin_wld['usd'],6);
            $total_oso = bcmul((string)$totalNum, (string)"1",6);
            $balance = $this->app(UserBalanceService::class)->findByUid($userId);
            if($total_oso > abs($balance['wld'])){
                throw new AppException('insufficient_wld',400);
            }
        }elseif($payType == 4){
            $total = bcmul((string)$totalNum, (string)$coin_atm['usd'],6);
            $total_oso = bcmul((string)$totalNum, (string)"1",6);
            $balance = $this->app(UserBalanceService::class)->findByUid($userId);
            if($total_oso > abs($balance['atm'])){
                throw new AppException('insufficient_atm',400);
            }
        }else{
            throw new AppException('payment_wrong',400);
        }

        $start_time = time() + 86400;
        $end_time = $start_time + ( 86400 *  ($pools->lever - 1));

        Db::beginTransaction();
        try {
            $record['user_id'] = $userId;
            $record['robot_id'] =  $pools->id;
            $record['order_sn'] = $this->makeOrdersn('PO');
            $record['symbol']  = 'WLD/ATM';
            $record['rate'] =  $pools->rate;
            $record['lever'] =  $pools->lever;
            $record['total'] = $total;
            $record['total_num'] = 0;
            $record['total_oso'] = $total_oso;
            $record['buy_time'] = date("Y-m-d H:i:s");
            $record['pay_time'] = date("Y-m-d H:i:s");
            $record['pay_type'] = $payType;
            $record['pay_series'] = $paySeries;
            $record['start_time'] = $start_time;
            $record['end_time'] = $end_time;
            $record['is_auto'] = 1;
            $record['aotu_num'] = 1;
            $record['income_time'] = time();
            $record['last_time'] = $start_time;
            $record['status'] = 1;
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            if($order->pay_type == 3){
                if($order->total_oso > 0){
                    $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,'wld',$balance['wld'],-$order->total_oso,7,'流动性挖矿扣除',$order->id);
                    if($res !== true){
                        throw new \Exception('资产失败');
                    }
                }
            }elseif ($order->pay_type == 4){
                if($order->total_oso > 0){
                    $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,'atm',$balance['atm'],-$order->total_oso,7,'流动性挖矿扣除',$order->id);
                    if($res !== true){
                        throw new \Exception('资产失败');
                    }
                }
            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[流动性报单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态-流动性*/
    public function reward($order,$rate_num){

            if ($order->income_time >=  strtotime(date('Y-m-d'))) {
                return false;
            }

            $rewardUsdt = bcmul((string)$order->total_oso,(string)$rate_num/100,6);
      
            if(time() >=  strtotime(date('Y-m-d',$order->end_time))){
                $status = 3;
            }else{
                $status = 2;
            }
            if($rewardUsdt > 0){
                //计算价格
                //$coin_wld = $this->app(SysConfigService::class)->value('wld_price');
                //$coin_atm = $this->app(SysConfigService::class)->value('atm_price');
                $coin_wld = $this->app(SysCoinsService::class)->findWhere('coin_symbol','wld');
                $coin_atm = $this->app(SysCoinsService::class)->findWhere('coin_symbol','atm');
                //币本位计算
                if($order->pay_type == 3){
                    $rewardWldUsdt =  bcmul((string)$rewardUsdt ,(string) $coin_wld['usd'],6);
                    $reward = bcdiv((string)$rewardUsdt ,(string) 1,6);
                }elseif ($order->pay_type == 4){
                    $rewardWldUsdt = 0;
                    $reward = bcdiv((string)$rewardUsdt ,(string) 1,6);
                }else{
                    return false;
                }
                $data = [
                    'income_time'=> time(),
                    'last_time'=> strtotime(date('Y-m-d')  . ' ' . date('H:i:s',$order->start_time)) + 86400,
                    'balance' => bcadd((string)$order->balance,(string)$rewardUsdt,6),//累计释放代币
                    'status' =>$status
                ];
            }else{
                $reward = 0;
                $data = [
                    'status' =>$status
                ];
            }
            //复投
            $isFutu =  $order->is_auto;
            if($status == 3 && $isFutu){
                $start_time = strtotime(date('Y-m-d')  . ' ' . date('H:i:s',$order->start_time)) + 86400;
                $end_time = $start_time + ( 86400 *  ($order->lever-1));
                $data['status'] = 2;
                $data['start_time'] = $start_time;
                $data['end_time'] = $end_time;
                $data['aotu_num'] = $order->aotu_num + 1;
            }


        Db::beginTransaction();
        try {
            $this->logic->getQuery()->where(['id'=>$order->id,'balance'=>$order->balance])->update($data);
            $power = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            if ($order->robot_id == 2){
                if($reward > 0 && $order->pay_type == 3){
                    $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'wld', $power['wld'], $reward, 13, '流动性挖矿释放', $order->id);
                    if ($rel !== true) {
                        throw new \Exception('更新资产失败');
                    }
                }
                if($reward > 0 && $order->pay_type == 4){
                    $reward = bcmul((string)$reward,'0.95',6);
                    $ref = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'atm', $power['atm'], $reward, 13, '流动性挖矿释放', $order->id);
                    if ($ref !== true) {
                        throw new \Exception('更新资产失败');
                    }
                }
                if($reward > 0){
                    $record = [
                        'user_id'=>$order->user_id,
                        'order_id'=>$order->id,
                        'robot_id'=>$order->robot_id,
                        'price'=> $order->pay_type == 3 ? $coin_wld['usd'] : 0,
                        'symbol'=>'wld_atm',
                        'rate'=>$rate_num,
                        'counts'=>$rewardWldUsdt,////今日收益wld的U
                        'reward_wld'=>  $order->pay_type == 3 ? $reward : 0,//今日收益代币
                        'reward_atm'=>  $order->pay_type == 4 ? $reward : 0,//今日收益代币
                        'reward_type'=> 1,//类型
                        'reward_time'=>$data['income_time'],
                        'dnamic_time'=>time(),
                        'groups_time'=>time(),
                    ];
                    $this->app(UserRadotIncomeService::class)->getQuery()->insert($record);
                }

                //不复投到期-自动撤销-归还本金
                if($data['status'] == 3){
                    $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                    if($order->total_oso > 0 && $order->pay_type == 3){
                        $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'wld', $balance['wld'], $order->total_oso, 10, '流动性挖矿撤单', $order->id);
                        if ($rel !== true) {
                            throw new \Exception('更新资产失败');
                        }
                    }
                    if($order->total_oso > 0 && $order->pay_type == 4){
                        $ref = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'atm', $balance['atm'], $order->total_oso, 10, '流动性挖矿撤单', $order->id);
                        if ($ref !== true) {
                            throw new \Exception('更新资产失败');
                        }
                    }
                }

            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[流挖矿释放]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


}