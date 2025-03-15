<?php


namespace App\Common\Service\Users;

use App\Common\Model\Users\UserLockedOrder;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRechargeLogic;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserRechargeService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserRechargeLogic
     */
    public function __construct(UserRechargeLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllType()
    {
        return [
            ['id'=>1,'title'=>'系统充值'],

            ['id'=>2,'title'=>'手动充值'],

            ['id'=>3,'title'=>'BRC充值'],

            ['id'=>4,'title'=>'TRC充值'],

            ['id'=>5,'title'=>'ETH充值'],

            ['id'=>17,'title'=>'亏损补贴'],


        ];
    }

    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '系统充值';
            case 2:
                return '手动充值';
            case 3:
                return 'BRC充值';
            case 4:
                return 'TRC充值';
            case 5:
                return 'ETH充值';
            case 17:
                return '亏损补贴';
        }
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){
        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('is_bind','email','mobile','username','id')->with('counts:user_id,recharge');

        }])->with(['extend:pid,tx_id,from,to'])->orderBy('created_at', 'desc')->paginate($perPage,['*'],'page',$page);
        $list->each(function ($item){
            $qudao = $this->app(UserService::class)->getQudaoByUser($item['user_id'],false);
            if($qudao){
                $item['account'] =  $qudao;
            }else{
                $item['account'] =   "";
            }
            $item['recharge'] =   $item['user']['counts']['recharge'];

            return $item;
        });
        return $list;
    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username','extend:pid,tx_id,from,to'])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造-导出
     */
    public function searchExp(array $where){
        $list = $this->logic->search($where)->with(['user:id,username','extend:pid,tx_id,from,to'])->get();
        return $list;
    }

    /**
     * 添加
     */
    public function create($userId,$data){
        $exists = $this->logic->getQuery()->where('user_id',$userId)->where('recharge_status',1)->exists();
        if ($exists) {
            throw new AppException('上一笔审核中,稍后再试！！',400);
        }
        $data['order_sn']   =  $this->makeOrdersn('CZ');
        $data['user_id']   =  $userId;
        $data['order_amount']   = $data['number'];
        $data['recharge_id'] = 0;
        $data['order_rate']   =  $data['rate'];
        $data['order_mone']   = bcmul($data['number'],$data['rate'],6);
        $data['order_coin']   =  $data['coin'];
        $data['order_type']   =  $data['order_type'];
        $data['remark']   =  $data['remark'] ?? '';
        return $this->logic->create($data);
    }

    /**
     * 创建连上订单
     */
    public function found($data){
        $user = $this->app(UserService::class)->find($data['user_id']);
        if(!$user){
            throw new AppException('用户不存在',400);
        }
        $count = $this->logic->getQuery()->where(['recharge_id'=>$data['recharge_id']])->count();
        if ($count) {
            throw new AppException('订单已存在',400);
        }
        //通道     bsc   eth    trx  op
        $series = ['1'=>3,'3'=>4,];
        // 1成功 2失败 4已轨迹，未上分，3待确认
        $_data['order_sn']   =  $this->makeOrdersn('CZ');
        $_data['user_id']   =  $data['user_id'];
        $_data['recharge_id'] = $data['recharge_id'];
        $_data['order_amount']   = $data['amount'];
        $_data['order_rate']   =  1;
        $_data['order_mone']   = bcmul($data['amount'],1,6);
        $_data['order_coin']   =  strtolower($data['symbol']);
        $_data['order_type']   =  $series[intval( $data['series_id'])];
        $_data['remark']       = '';
        Db::beginTransaction();
        try {
            $res =  $this->logic->create($_data);
            if (!$res){
                throw new \Exception('主订单失败');
            }
            $insert['recharge_id'] = $res->recharge_id;
            $insert['pid'] = $res->order_id;
            $insert['tx_id'] = $data['tx_id'];
            $insert['from'] = $data['from'];
            $insert['to'] = $data['to'];
            $insert['status'] = $data['status'];
            $rel =  $this->app(UserRechargeServiceEx::class)->getQuery()->insert($insert);
            if (!$rel){
                throw new \Exception('子订单失败');
            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[充值下单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }
    }

    /*确定订单，上分 后台*/
    public function confirm($id){

        $order = $this->logic->getQuery()->where('order_id',$id)->where('recharge_status',1)->first();

        if (!$order) {
            throw new AppException('订单已处理',400);
        }

        Db::beginTransaction();
        try {
            $data['recharge_status']   =  2;

            //通过增加
            if($this->logic->update($id,$data)){
                if($order->order_type == 1){
                    $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                    $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($order->order_coin),$balance[strtolower($order->order_coin)],$order->order_mone,1,'系统充值');
                }else{
                    $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                    $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($order->order_coin),$balance[strtolower($order->order_coin)],$order->order_mone,2,'用户充值');
                }
                //充值解开提现权限
                $this->app(UserService::class)->getQuery()->where('id',$order->user_id)->update(['is_lock'=>0]);
            }
            Db::commit();
            $this->app(UserCountService::class)->getSelfYeji($order->user_id);
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[充值确认]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }


    /*充值送保险卷*/
    public function safety($order_mone){
        $recharge_safety = 0;// 0 不送  >=1 送多少张
        if(0>=$this->app(SysConfigService::class)->value('recharge_safety_switch')){
            return $recharge_safety;
        }
        if($order_mone >= 1500 && $order_mone < 3000 ){
            $recharge_safety = 1;
        }elseif ($order_mone >= 3000 && $order_mone < 6000 ){
            $recharge_safety = 2;
        }elseif ($order_mone >= 6000){
            $recharge_safety = 4;
        }
        return $recharge_safety;
    }

    /*回调上分 -自动*/
    public function finish($data=[]){

        $order = $this->logic->getQuery()->where(['recharge_id'=>$data['recharge_id']])->where('recharge_status',1)->first();
        if (!$order) {
            throw new AppException('订单已处理',400);
        }
        if($data['status'] == 3 || $data['status'] == 2 || strtolower($order->order_coin) != "usdt"){
            return true;
        }

        Db::beginTransaction();
        try {
            // 1成功 2失败 4已轨迹，未上分，3待确认
            if($data['status'] == 4){//$is_collect
                $record['recharge_status'] = 4;
            }else if($data['status'] == 5){//$is_collect
                $record['recharge_status'] = 4;
            }else if($data['status'] == 1){
                $record['recharge_status'] = 2;
                $record['recharge_safety'] = $this->safety($order->order_mone);//送保险卷
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            $onenum = $this->logic->getQuery()->where(['user_id'=>$order->user_id])->where('recharge_status',2)->count();
            if(0>=$onenum){
                $record['recharge_at'] = date('Y-m-d H:i:s');//首充时间
                //充值解开提现权限
                $this->app(UserService::class)->getQuery()->where('id',$order->user_id)->update(['is_lock'=>0]);
                //扣除注册赠送金到锁仓-计划任务扣除
                //if( 0 >= $this->app(UserLockedOrderService::class)->getQuery()->where('user_id',$order->user_id)->where('order_type',1)->count()){
                    //$this->app(UserSecondService::class)->checkRegisLock($order->user_id,1);
                    //$this->app(UserLockedOrderService::class)->create(['user_id'=>$order->user_id,'order_type'=>1,'order_num'=>$balance['usdt']]);
                //}
            }
            $is_recharge_yeji = 0;
            //通过增加
            if(!$this->logic->getQuery()->where(['recharge_status'=>1,'recharge_id'=>$data['recharge_id']])->update($record)){
                throw new \Exception('订单更新失败');
            }
            if( $record['recharge_status'] == 2 ){
                $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($order->order_coin),$balance[strtolower($order->order_coin)],$order->order_mone,2,'在线上分');
                if($res !== true){
                    throw new \Exception('订单上分失败');
                }
                $is_recharge_yeji = 1;
            }

            $rel =  $this->app(UserRechargeServiceEx::class)->getQuery()->where('recharge_id',$data['recharge_id'])->update(['status'=>$data['status']]);
            if (!$rel){
                throw new \Exception('子订单失败');
            }

            Db::commit();
            if($is_recharge_yeji == 1){
                $this->app(UserCountService::class)->getSelfYeji($order->user_id);
            }
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[充值上分]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }
    /*回调上分 -手动*/
    public function finishUps($id,$remark){

        $order = $this->logic->getQuery()->where(['order_id'=>$id])->whereIn('recharge_status',[4])->first();
        if (!$order) {
            throw new AppException('订单已处理',400);
        }

        Db::beginTransaction();
        try {
            // 1成功 2失败 4已轨迹，未上分，3待确认
            $record['recharge_status'] = 2;
            $record['recharge_safety'] = $this->safety($order->order_mone);//送保险卷
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            $onenum = $this->logic->getQuery()->where(['user_id'=>$order->user_id])->where('recharge_status',2)->count();
            if(0>=$onenum){
                $record['recharge_at'] = date('Y-m-d H:i:s');
                //充值解开提现权限
                $this->app(UserService::class)->getQuery()->where('id',$order->user_id)->update(['is_lock'=>0]);
                //扣除注册赠送金到锁仓-计划任务扣除
                //if( 0 >= $this->app(UserLockedOrderService::class)->getQuery()->where('user_id',$order->user_id)->where('order_type',1)->count()){
                //    $this->app(UserSecondService::class)->checkRegisLock($order->user_id,1);
                //    $this->app(UserLockedOrderService::class)->create(['user_id'=>$order->user_id,'order_type'=>1,'order_num'=>$balance['usdt']]);
                //}
            }
            $is_recharge_yeji = 0;
            //通过增加
            if(!$this->logic->getQuery()->where(['recharge_status'=>4,'order_id'=>$order->order_id])->update($record)){
                throw new \Exception('订单更新失败');
            }
            if( $record['recharge_status'] == 2){
                $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($order->order_coin),$balance[strtolower($order->order_coin)],$order->order_mone,2,$remark);
                if($res !== true){
                    throw new \Exception('订单上分失败');
                }
                $is_recharge_yeji = 1;
            }

            $rel =  $this->app(UserRechargeServiceEx::class)->getQuery()->where('recharge_id',$order->recharge_id)->update(['status'=>1]);
            if (!$rel){
                throw new \Exception('子订单失败');
            }

            Db::commit();
            if($is_recharge_yeji == 1){
                $this->app(UserCountService::class)->getSelfYeji($order->user_id);
            }
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[充值上分]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }
    /*回调归集 -自动*/
    public function collect($data=[]){
        $order = $this->logic->getQuery()->where(['recharge_id'=>$data['recharge_id']])->where('recharge_status',1)->first();
        if (!$order) {
            return false;
        }
        if($data['is_collect'] != 1){
            return false;
        }
        $data['is_collect']   =  1;
        if(!$this->logic->getQuery()->where(['recharge_status'=>2,'order_id'=>$order->id])->update($data)) return false;
        return true;
    }

    public function cancel($id,$detail='用户取消'){
        $exists = $this->logic->getQuery()->where('order_id',$id)->where('recharge_status',1)->exists();
        if (!$exists) {
            throw new AppException('订单已处理',400);
        }
        $data['recharge_status']   =  0;
        $data['detail']  =  $detail;
        if(!$this->logic->getQuery()->where(['recharge_status'=>1,'order_id'=>$id])->update($data)) throw new AppException('操作失败');
        return true;
    }

    /*保险卷动态*/
    public function dnamicSafety($order){
        if ($order->safety_time > 0) {
            throw new AppException('订单已完成',400);
        }
        if ($order->recharge_status != 2) {
            throw new AppException('订单未付款',400);
        }
        //上1代 + 自身
        $parent = $this->app(UserRelationService::class)->getParent($order->user_id,1);
        $pid= 0;
        if(count($parent) > 0){$pid = $parent[0];}

        Db::beginTransaction();
        try {
            $res =  $this->logic->getQuery()->where(['order_id'=>$order->order_id,'safety_time'=>0])->update(['safety_time'=>time()]);
            if(!$res){
                throw new \Exception('更新失败');
            }
            if($pid > 0){
                $userReward = $this->app(UserRewardService::class)->findByUid($pid);
                $safety_number = intval(bcadd((string)$userReward->safety_number,(string)$order->recharge_safety,4));
                $rel = $this->app(UserRewardService::class)->getQuery()->where('id',$userReward->id)->where('safety_number',$userReward->safety_number)->update(['safety_number'=>$safety_number]);
                if(!$rel){
                    throw new \Exception('赠送失败');
                }
            }

            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[充值送保险]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }







}