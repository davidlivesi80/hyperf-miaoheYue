<?php


namespace App\Common\Service\Users;

use App\Common\Model\Users\UserReward;
use App\Common\Service\System\SysCardsService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysContractService;
use App\Common\Service\System\SysRobotService;
use App\Common\Service\System\SysSportRuningService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRobotLogic;
use Upp\Exceptions\AppException;
use Upp\Service\BnbService;
use Upp\Service\SignService;
use Upp\Traits\HelpTrait;

class UserRobotService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserRobotLogic
     */
    public function __construct(UserRobotLogic $logic)
    {
        $this->logic = $logic;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','ucard:id,card_sn,card_id,status,price'])->paginate($perPage,['*'],'page',$page);

        $list->each(function ($item){
            $item['balance'] = $this->app(UserRobotIncomeService::class)->reward(['order_id'=>$item['id'],'reward_type'=>1]);
            //$bili = $this->getBili($item['bili']);
            $item['rate'] = bcadd((string)$item['rate'],(string)$item['bili'],4);
            $item->ucard->card;
            $item['total'] = bcadd((string)$item['total'],(string)$item['price'],4);
            return $item;
        });
        return $list;

    }

    public function getBili($settle_content){
        $sport_settle_rate = explode('@',$this->app(SysConfigService::class)->value('sport_settle_rate'));
        if($settle_content == 1){
            return $sport_settle_rate[0];
        }elseif ($settle_content == 2){
            return $sport_settle_rate[1];
        }else{
            return 0;
        }
    }
    

    /**
     * 添加
     */
    public function create($userId,$cardId,$timerId){

        $ucard = $this->app(UserCardsService::class)->getQuery()->where('user_id',$userId)->where('id',$cardId)->where('status',1)->first();
        if(!$ucard){
            throw new AppException('user_card_error',400);
        }
        if($ucard->robot_oid > 0){
            throw new AppException('user_card_robot',400);
        }
        if($ucard->status ==0){
            throw new AppException('user_card_error',400);
        }
        $attr_arr = $ucard->card->attr_arr;
        $attr_ids = array_column($attr_arr,'id');
        $keys = array_search($timerId,$attr_ids);
        if($keys === false){
            throw new AppException('user_card_timer',400);
        }
        $timer = $attr_arr[$keys];//周期
        $start_time = strtotime(date('Y-m-d')) + 86400;
        $end_time = $start_time + ( 86400 *  $timer['timer']);
        
        $runing = $this->app(SysSportRuningService::class)->getQuery()->whereDate('created_at',date('Y-m-d'))->first();
        
        Db::beginTransaction();
        try {
            $record['user_id'] = $userId;
            $record['ucard_id'] =  $ucard->id;
            $record['card_id'] = $ucard->card_id;
            $record['order_sn'] = $this->makeOrdersn('PO');
            $record['price']  = $ucard->price;
            $record['rate'] =  bcadd((string)$timer['rate'], (string)$ucard->card->rate,4); //周期比例 + 卡片比例;
            $record['bili'] =  $runing ? $runing->settle_content : 0 ;//今日赛事比例，赢取次数，每日静态完成重置
            $record['timer'] =  $timer['id'];
            $record['timer_rate'] =  $timer['rate'];
            $record['total'] =  $ucard->amount;
            $record['buy_time'] = date("Y-m-d H:i:s");
            $record['pay_time'] = date("Y-m-d H:i:s");
            $record['pay_type'] = 1;
            $record['pay_series'] = 1;
            $record['start_time'] = $start_time;
            $record['end_time'] = $end_time;
            $record['income_time'] = time();
            $record['status'] = 1;
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //锁定卡片
            $rel = $this->app(UserCardsService::class)->getQuery()->where('id',$ucard->id)->update(["robot_oid"=>$order->id]);
            if(!$rel){
                throw new \Exception('锁定失败');
            }
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[质押卡片]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    public function cancel($order){
        
        if(!$order || $order->status == 0){
            throw new AppException('订单已处理',400);
        }

        $ucard = $this->app(UserCardsService::class)->getQuery()->where('user_id',$order->user_id)->where('id',$order->ucard_id)->first();

        Db::beginTransaction();
        try {
            $this->logic->getQuery()->where(['id'=>$order->id])->update(['status'=>0]);
            //锁定卡片
            $rel = $this->app(UserCardsService::class)->getQuery()->where('id',$ucard->id)->update(["robot_oid"=>0]);
            if(!$rel){
                throw new \Exception('解锁失败');
            }
            Db::commit();
            //更新业绩
           return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[挖矿撤单-手动]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*动态统计*/
    public function quicken($order){
        Db::beginTransaction();
        try {
            //更新奖金-团队
            if($order->reward_type == 1){
                $dnamic = $this->app(UserRobotQuickenService::class)->getQuery()->where('user_id',$order->user_id)->where("reward_type",1)->sum("reward");
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['groubs'=>$dnamic]);
            //更新奖金-平级
            }elseif($order->reward_type == 2){
                $groups = $this->app(UserRobotQuickenService::class)->getQuery()->where('user_id',$order->user_id)->where("reward_type",2)->sum("reward");
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['groups'=>$groups]);
            }
            $this->app(UserRobotQuickenService::class)->getQuery()->where('id',$order->id)->update(['quicken_time'=>time()]);
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[奖金统计]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态*/
    public function income($order){
        if ($order->income_time >=  strtotime(date('Y-m-d'))) {
            return false;
        }
        $total = bcadd((string)$order->total , (string)$order->price,6);
        //$bili = $this->getBili($order->bili);//今日赛事比例，赢取次数，每日静态完成重置
        $rate = bcadd((string)$order->rate,(string)$order->bili,4); //周期比例 + 卡片比例 + 赛事比例 = 总比例
        $rewardUsdt = bcmul($total, $rate/100,6);
        if(time() >=  strtotime(date('Y-m-d',$order->end_time))){
            $status = 3;
        }else{
            $status = 2;
        }
        if($rewardUsdt > 0){
            $data = [
                'income_time'=> time(),
                'bili' => 0,//今日赛事比例，赢取次数，每日静态完成重置
                'reward'=> $rewardUsdt,//今日释放
                'balance' => bcadd((string)$order->balance,(string)$rewardUsdt,6), //累计释放
                'status' =>$status
            ];
        }else{
            $data = [
                'bili' =>0,
                'status' =>$status
            ];
        }

        Db::beginTransaction();
        try {
            $this->logic->getQuery()->where(['id'=>$order->id,'balance'=>$order->balance])->update($data);
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            if($rewardUsdt > 0){
                $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $rewardUsdt, 9, '卡片质押静态', $order->id);
                if ($rel !== true) {
                    throw new \Exception('更新资产失败');
                }
            }
            if($rewardUsdt > 0){
                $record = [
                    'user_id'=>$order->user_id,
                    'order_id'=>$order->id,
                    'ucard_id'=>$order->ucard_id,
                    'total'=>$total,
                    'symbol'=>'usdt',
                    'rate'=>$rate,
                    'reward'=> $rewardUsdt,//今日收益代币
                    'reward_type'=> 1,//类型
                    'reward_time'=>$data['income_time'],
                ];
                $this->app(UserRobotIncomeService::class)->getQuery()->insert($record);
            }

            Db::commit();
            //更新奖金
            $income = $this->app(UserRobotIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',1)->sum('reward');
            if($income >= 0){
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['income'=>$income]);
            }
            $this->app(UserCountService::class)->getSelfYeji($order->user_id);
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[质押释放]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*团队*/
    public function groups($orderIncome,$groupRate,$groupPins){
        if ($orderIncome->groups_time > 0) {
            $detailes[] = "团队奖励[订单-{$orderIncome->id}，已执行]";
            return $detailes;
        }
        $detailes =[];
        $parents = $this->app(UserRelationService::class)->getParent($orderIncome->user_id);
        $parentsMew = [];
        //查询等级
        for ($i = 0; $i < count($parents); $i++) {
            $extend = $this->app(UserExtendService::class)->findByUid($parents[$i]);
            if($extend->level == 1){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[0];
                $parentOne['level'] = 1;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }elseif($extend->level == 2){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[1];
                $parentOne['level'] = 2;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }elseif($extend->level == 3){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[2];
                $parentOne['level'] = 3;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }elseif($extend->level == 4){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[3];
                $parentOne['level'] = 4;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }elseif($extend->level == 5){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[4];
                $parentOne['level'] = 5;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }elseif($extend->level == 6){
                $parentOne['id']  = $extend->user_id;
                $parentOne['rate'] = $groupRate[5];
                $parentOne['level'] = 6;
                $parentOne['pin'] = 0;
                $parentsMew[] = $parentOne;
            }
        }
        $this->logger('[团队奖励]','robot')->info(json_encode($parentsMew,JSON_UNESCAPED_UNICODE));
        //排查平级
        $parentsList = []; $pinsIds = 0;$pinsRate=0;
        for ($j = 0; $j < count($parentsMew); $j++) {
            $parent = $parentsMew[$j];
            if ($j == 0) {
                $parentsList[] = $parent;
                continue;
            }
            if($parentsMew[$j]['level'] > $parentsMew[$j-1]['level']){
                $parentsList[] = $parent;
                $pinsIds = 0;
                $pinsRate = 0;
            }else{
                if($pinsIds == 0){
                    $parent['pin']  = $pinsIds = $parentsMew[$j-1]['id'];
                    $parent['rate'] = $pinsRate = bcmul((string)$parentsMew[$j-1]['rate'],(string)$groupPins,6);//平级第一层得20%
                }else{
                    $parent['pin'] = $pinsIds;
                    $parent['rate'] = $pinsRate;
                }
                $parentsList[] = $parent;
            }
        }
        $this->logger('[团队奖励]','robot')->info(json_encode($parentsList,JSON_UNESCAPED_UNICODE));
        //发放奖金
        Db::beginTransaction();
        try {
            foreach ($parentsList as $upd){
                //$first =  $this->app(UserCountService::class)->findByUid($upd['id']);
                //奖金条件
//                if(0 >= $first->self){
//                    $detailes[] = "团队奖励[用户-{$upd['id']}，未购买]";
//                    continue;
//                }
                //计算奖金
                $rewardUsdt = bcmul((string)$orderIncome->reward, (string)$upd['rate'],6);
                if(0>=$rewardUsdt){
                    $detailes[] = "团队奖励[用户-{$upd['id']}，奖金为0]";
                    continue;
                }
                if($upd['pin'] > 0){
                    $remark = '来源：用户'.$upd['pin'].'平级奖励';
                    $balance = $this->app(UserBalanceService::class)->findByUid($upd['id']);
                    if($rewardUsdt > 0){
                        $rel = $this->app(UserBalanceService::class)->rechargeTo($upd['id'], 'usdt', $balance['usdt'], $rewardUsdt, 11, $remark, $orderIncome->id,$upd['pin']);
                        if ($rel !== true) {
                            throw new \Exception('更新资产失败');
                        }
                    }
                }else{
                    $remark = '来源：用户'.$orderIncome->user_id.'团队奖励';
                    $balance = $this->app(UserBalanceService::class)->findByUid($upd['id']);
                    if($rewardUsdt > 0){
                        $rel = $this->app(UserBalanceService::class)->rechargeTo($upd['id'], 'usdt', $balance['usdt'], $rewardUsdt, 10, $remark, $orderIncome->id,$orderIncome->user_id);
                        if ($rel !== true) {
                            throw new \Exception('更新资产失败');
                        }
                    }
                }

                if($rewardUsdt > 0 ){
                    $record = [
                        'user_id'=>$upd['id'],
                        'order_id'=>$orderIncome->id,
                        'ucard_id'=>$orderIncome->ucard_id,
                        'target_id'=>$upd['pin'] > 0 ? $upd['pin']: $orderIncome->user_id,
                        'total'=>$orderIncome->reward,
                        'symbol'=>'usdt',
                        'rate'=>$upd['rate'],
                        'reward'=> $rewardUsdt,//今日收益
                        'reward_type'=> $upd['pin'] > 0 ? 2 : 1,//团队，平静
                        'reward_time'=>time(),
                    ];
                    $this->app(UserRobotQuickenService::class)->getQuery()->insert($record);
                    $this->app(UserCountService::class)->getSelfYeji($upd['id']);
                }

                $detailes[] = "[用户-{$upd['id']}-{$upd['level']}，已达标，订单{$orderIncome->id}奖金：{$rewardUsdt}]";

            }
            $this->app(UserRobotIncomeService::class)->getQuery()->where(['id'=>$orderIncome->id,'groups_time'=>0])->update(['groups_time'=>time()]);
            Db::commit();
            $this->getCache()->delete('groupsRobot_'.$orderIncome->id);
            $this->logger('[团队奖励]','robot')->info(json_encode($detailes,JSON_UNESCAPED_UNICODE));
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $this->getCache()->delete('groupsRobot_'.$orderIncome->id);
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[团队奖励]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

}