<?php


namespace App\Common\Service\Users;

use App\Common\Model\Users\UserPowerOrder;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysContractService;
use App\Common\Service\System\SysRobotService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserPowerOrderLogic;
use Upp\Exceptions\AppException;
use Upp\Service\BnbService;
use Upp\Service\SignService;
use Upp\Traits\HelpTrait;


class UserPowerOrderService extends BaseService
{
    use HelpTrait;


    /**
     * @var UserPowerOrderLogic
     */
    public function __construct(UserPowerOrderLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);

        $list->each(function ($item){

            return $item;
        });

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
    public function create($userId,$totalNum,$paySeries,$payType,$buyType){
        $lastTime = mktime(23,50,0,date('m'),date('d'),date('Y'));
        if(time() >= $lastTime){
           throw new AppException('Wrong_power_try_later',400);
        }
        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if (!$powerInsert){
            throw new AppException('Wrong_power_try_later',400);
        }
        $pools = $this->app(SysRobotService::class)->find(3);
        if(!$pools){
            throw new AppException('Wrong_pools',400);
        }
        if ($pools->price > $totalNum) {
            throw new AppException('Amount_is_too_small',400);//投资金额太少
        }

        $coin_wld = $this->app(SysCoinsService::class)->findWhere('coin_symbol','wld');
        $coin_atm = $this->app(SysCoinsService::class)->findWhere('coin_symbol','atm');
        $total_oss = 0;
        if($payType == 3){
            $total_oso = bcmul((string)$totalNum, (string) 1,6);
            $balance = $this->app(UserBalanceService::class)->findByUid($userId);
            if($total_oso > abs($balance['wld'])){
                throw new AppException('Insufficient_wld',400);
            }
        }elseif($payType == 4){
            $total_oso = bcmul((string)$totalNum, (string) 1,6);
            $balance = $this->app(UserBalanceService::class)->findByUid($userId);
            if($total_oso > abs($balance['atm'])){
                throw new AppException('Insufficient_atm',400);
            }
        }else{
            throw new AppException('Wrong_payment',400);
        }

        Db::beginTransaction();
        try {
            $record['user_id'] = $userId;
            $record['robot_id'] =  $pools->id;
            $record['symbol']  = 'oss';
            $record['order_sn'] = $this->makeOrdersn('PO');
            $record['total'] =  $totalNum;
            $record['total_num'] = 0;
            $record['total_oso'] = $total_oso;
            $record['buy_time'] = date("Y-m-d H:i:s");
            $record['pay_time'] = date("Y-m-d H:i:s");
            $record['buy_type'] = $buyType;
            $record['pay_type'] = $payType;
            $record['pay_series'] = $paySeries;
            $record['income_time'] = time();
            $record['status'] = 1;
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('Creation failed');//创建失败
            }
            if($order->pay_type == 3){
                if($order->total_oso > 0){
                    $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,'wld',$balance['wld'],-$order->total_oso,6,'复利挖矿',$order->id);
                    if($res !== true){
                        throw new \Exception('asset_failed');//资产失败
                    }
                    $power = $this->app(UserPowerService::class)->findByUid($order->user_id);
                    $ref = $this->app(UserPowerService::class)->rechargeTo($order->user_id, 'wld', $power['wld'], $order->total_oso, 8, "复利加入" , $order->id);
                    if ($ref !== true) {
                        throw new \Exception('asset_failed');
                    }

                }
            }elseif ($order->pay_type == 4){
                if($order->total_oso > 0){
                    $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,'atm',$balance['atm'],-$order->total_oso,6,'复利挖矿',$order->id);
                    if($res !== true){
                        throw new \Exception('asset_failed');//资产失败
                    }
                    $power = $this->app(UserPowerService::class)->findByUid($order->user_id);
                    $ref = $this->app(UserPowerService::class)->rechargeTo($order->user_id, 'atm', $power['atm'], $order->total_oso, 8, "复利加入" , $order->id);
                    if ($ref !== true) {
                        throw new \Exception('asset_failed');
                    }
                }
            }
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[复利池]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /**
     * 取消
     */
    public function found($userId,$totalNum,$paySeries,$payType,$buyType){
        $lastTime = mktime(23,50,0,date('m'),date('d'),date('Y'));
        if(time() >= $lastTime){
           throw new AppException('Wrong_power_try_later',400);
        }
        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if (!$powerInsert){
            throw new AppException('Wrong_power_try_later',400);
        }
        $pools = $this->app(SysRobotService::class)->find(3);
        if(!$pools){
            throw new AppException('Wrong_pools',400);
        }
        
        $coin_wld = $this->app(SysCoinsService::class)->findWhere('coin_symbol','wld');
        $coin_atm = $this->app(SysCoinsService::class)->findWhere('coin_symbol','atm');
        $total_oss = 0;
        if($payType == 3){
            $total_oso = bcmul((string)$totalNum, (string) 1,6);
            $power = $this->app(UserPowerService::class)->findByUid($userId);
            if($total_oso > abs($power['wld'])){
                throw new AppException('Insufficient_wld',400);
            }
        }elseif($payType == 4){
            $total_oso = bcmul((string)$totalNum, (string) 1,6);
            $power = $this->app(UserPowerService::class)->findByUid($userId);
            if($total_oso > abs($power['atm'])){
                throw new AppException('Insufficient_atm',400);
            }
        }else{
            throw new AppException('Wrong_payment',400);
        }

        Db::beginTransaction();
        try {
            $record['user_id'] = $userId;
            $record['robot_id'] =  $pools->id;
            $record['symbol']  = 'oss';
            $record['order_sn'] = $this->makeOrdersn('PO');
            $record['total'] =  $totalNum;
            $record['total_num'] = 0;
            $record['total_oso'] = $total_oso;
            $record['buy_time'] = date("Y-m-d H:i:s");
            $record['pay_time'] = date("Y-m-d H:i:s");
            $record['buy_type'] = $buyType;
            $record['pay_type'] = $payType;
            $record['pay_series'] = $paySeries;
            $record['income_time'] = time();
            $record['status'] = 1;
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('Creation failed');//创建失败
            }
            if($order->pay_type == 3){
                if($order->total_oso > 0){
                    $res =  $this->app(UserPowerService::class)->rechargeTo($order->user_id,'wld',$power['wld'],-$order->total_oso,10,'复利池提取',$order->id);
                    if($res !== true){
                        throw new \Exception('asset_failed');//资产失败
                    }
                    $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                    $ref = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'wld', $balance['wld'], $order->total_oso, 12, "复利池提取" , $order->id);
                    if ($ref !== true) {
                        throw new \Exception('asset_failed');
                    }
                }
            }elseif ($order->pay_type == 4){
                if($order->total_oso > 0){
                    $res =  $this->app(UserPowerService::class)->rechargeTo($order->user_id,'atm',$power['atm'],-$order->total_oso,10, '复利池提取',$order->id);
                    if($res !== true){
                        throw new \Exception('asset_failed');//资产失败
                    }
                    $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                    $amount = bcmul((string)$order->total_oso,'0.95',6);
                    if($amount > 0){
                        $ref = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'atm', $balance['atm'], $amount, 12, "复利池提取", $order->id);
                        if ($ref !== true) {
                            throw new \Exception('asset_failed');
                        }
                    }
                    
                }
            }
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[复利撤]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*静态*/
    public function income($userPower,$rate,$wld_price){
        if($userPower->income_time > strtotime(date("Y-m-d"))){
            return false;
        }
        Db::beginTransaction();
        try {
            $reward_wld = bcmul((string)$userPower->pools_wld,(string)($rate/100),6);
            $reward_atm = bcmul((string)$userPower->pools_atm,(string)($rate/100),6);
            $power = $this->app(UserPowerService::class)->findByUid($userPower->user_id);
            $count = $this->app(UserCountService::class)->findByUid($userPower->user_id);
            //收益暂停条件
            //累计收益 = 双币静态 + 双币动态 +双币团队、平级 + 复利静态 \usdt 卡 wld   atm 卡 ATM
            //剩余usdt奖金 = 双币持仓USDT(所有订单支付usdt金额) - 累计收益wld价值
            $total_wld_usdt = bcadd((string)$power->robot_wld_usdt,(string)$power->quicken_wld_usdt,6);
            $total_wld_usdt = bcadd((string)$total_wld_usdt,(string)$power->power_wld_usdt,6);
            $total_wld_usdt = bcadd((string)$total_wld_usdt,(string)$power->total_wld_usdt,6);
            $reward_wld_surplus = bcsub((string)$count->self_usdt,(string)$total_wld_usdt,6);
            if( $reward_wld > $reward_wld_surplus){
                $reward_wld = $reward_wld_surplus;
            }
            //剩余atm奖金 = 双币持仓ATM(所有订单支付atm金额) - 累计收益atm 团队 分享 平级
            $reward_atm_surplus = bcsub((string)$count->self_atm,(string)$power->total_atm,6);
            if( $reward_atm > $reward_atm_surplus ){
                $reward_atm = $reward_atm_surplus;
            }
            if($reward_wld > 0) {
                $rel = $this->app(UserPowerService::class)->rechargeTo($userPower->user_id, 'wld', $power['wld'], $reward_wld, 9, '复利释放',0);
                if ($rel !== true) {
                    throw new \Exception('更新资产失败');
                }
            }
            if($reward_atm > 0) {
                $reward_atm = 0;
                // $rel = $this->app(UserPowerService::class)->rechargeTo($userPower->user_id, 'atm', $power['atm'], $reward_atm, 9, '复利释放',0);
                // if ($rel !== true) {
                //     throw new \Exception('更新资产失败');
                // }
            }
            if($reward_wld > 0 || $reward_atm > 0){
                $record = [
                    'user_id'=>$userPower->user_id,
                    'robot_id'=>3,
                    'rate'=>$rate,
                    'price'=> $wld_price,
                    'symbol'=>'wld_atm',
                    'counts'=>bcmul((string)$reward_wld,(string)$wld_price,6),//今日收益wld的U
                    'pools_wld'=>$userPower->pools_wld,//今日本金
                    'pools_atm'=>$userPower->pools_atm,//今日本金
                    'reward_wld'=> $reward_wld,//今日收益代币
                    'reward_atm'=> $reward_atm,//今日收益代币
                    'reward_type'=> 1,//类型
                    'reward_time'=>time(),
                ];
                $this->app(UserPowerIncomeService::class)->getQuery()->insert($record);
            }
            
            Db::commit();
            //更新奖金
            $power_income_wld = $this->app(UserPowerIncomeService::class)->getQuery()->where('user_id',$userPower->user_id)->sum('reward_wld');
            $power_income_atm = $this->app(UserPowerIncomeService::class)->getQuery()->where('user_id',$userPower->user_id)->sum('reward_atm');
            $power_wld_usdt = $this->app(UserPowerIncomeService::class)->getQuery()->where('user_id',$userPower->user_id)->sum('counts');
            $this->app(UserPowerService::class)->getQuery()->where('id',$userPower->id)->update(['income_time'=>time(),'power_income_wld'=>$power_income_wld,'power_income_atm'=>$power_income_atm,'power_wld_usdt'=>$power_wld_usdt]);
            $this->logger('[复利矿池]','other')->info(json_encode(['msg'=>"今日发放{$reward_wld}-{$reward_atm},{$userPower->user_id}"],JSON_UNESCAPED_UNICODE));
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[异常]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }
    }



}