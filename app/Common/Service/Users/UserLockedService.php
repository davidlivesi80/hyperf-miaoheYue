<?php


namespace App\Common\Service\Users;

use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserLockedLogic;

class UserLockedService extends BaseService
{
    /**
     * @var UserLockedLogic
     */
    public function __construct(UserLockedLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询可用
     */
    public function searchBalance($userId){

        $balance = $this->logic->getQuery()->where(['user_id'=>$userId])->sum('lock_num');

        $lock_order_num = $this->app(UserLockedOrderService::class)->getQuery()->where(['user_id'=>$userId,'order_type'=>1,'lock_time'=>0])->sum('order_num');

        $balance = bcadd((string)$balance,(string)$lock_order_num,6);

        return $balance;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('mobile','email','is_bind','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('mobile','email','is_bind','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /*注册赠送金锁仓*/
    public function lock($userId,$number,$type,$oneRechange){

        Db::beginTransaction();
        try {
            $res =  $this->logic->getQuery()->create(['user_id'=>$userId,'lock_num'=>$number,'lock_type'=>$type]);
            if(!$res){
                throw new \Exception('插入失败');
            }
            if($number > 0){
                $balance = $this->app(UserBalanceService::class)->findByUid($userId);
                $subNum = bcmul((string)$number, '-1', 6);
                $rel = $this->app(UserBalanceService::class)->rechargeTo($userId,'usdt',$balance['usdt'],$subNum,26,'赠送金锁仓');
                if($rel !== true){
                    throw new \Exception('订单上分失败');
                }
            }
            if($oneRechange > 0){
                //解锁
                if( $this->app(UserService::class)->getQuery()->where('id',$userId)->update(['is_lock'=>0])){
                    $this->app(UserService::class)->is_lock($userId,3);
                }
            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[赠送金锁仓]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*注册赠送金解锁*/
    public function unlock($userId,$number,$type){
        Db::beginTransaction();
        try {
            $res =  $this->logic->getQuery()->create(['user_id'=>$userId,'lock_num'=>-$number,'lock_type'=>$type]);
            if(!$res){
                throw new \Exception('插入失败');
            }
            if($number > 0){
                $balance = $this->app(UserBalanceService::class)->findByUid($userId);
                $addNum = bcmul((string)$number, '1', 6);
                $rel = $this->app(UserBalanceService::class)->rechargeTo($userId,'usdt',$balance['usdt'],$addNum,27,'赠送金解锁');
                if($rel !== true){
                    throw new \Exception('订单上分失败');
                }
            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[赠送金锁仓]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*统计可解锁数量*/
    public function unlockNum($userId){
        $reward_one = '0'; $reward_two = '0';
        $balance = $this->searchBalance($userId);
        //1、解锁利润
        $self_recharge = $this->app(UserRechargeService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('created_at','>=','2025-03-01')->where('recharge_status',2)->sum("order_mone");
        $self_liushui = $this->app(UserSecondService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[0,3,4])->where('settle_status',1)->where('created_at','>=', '2025-03-01')->sum('num');
        if($self_recharge >= 300 && $self_liushui >= 300){
            $reward_one = bcsub((string)$balance,"300",6);
            if(0>=$reward_one ){
                $reward_one = '0';
            }
        }

        //2、解锁本金,直推净业绩满足3000，且考核期满足一个月，从三月一日充值开始算
//        $child_recharge = $this->app(UserRechargeService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('created_at','>=','2025-03-01')->where('recharge_status',2)->sum("order_mone");
//        $child_withdraw = $this->app(UserWithdrawService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('created_at','>=','2025-03-01')->where('withdraw_status',2)->sum("order_mone");
//        $child_jingyeji = bcsub($child_recharge,$child_withdraw,6);
//        if($child_jingyeji >= 3000){
//            $reward_two = bcsub((string)$balance,$reward_one,6);
//            if(0>=$reward_two ){
//                $reward_two = '0';
//            }
//        }
        $reward = bcadd($reward_one,$reward_two,6);
        return compact('reward','reward_one','reward_two','self_recharge','self_liushui');
    }



}