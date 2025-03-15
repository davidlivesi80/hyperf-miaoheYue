<?php


namespace App\Common\Service\Users;

use _PHPStan_b8e553790\Nette\Neon\Exception;
use App\Job\SecondJob;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\DtsRecordLogic;
use Upp\Exceptions\AppException;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysGameService;
use App\Common\Service\System\SysGameRuleService;
use Upp\Traits\HelpTrait;

class UserGameService extends BaseService
{
    use HelpTrait;
    /**
     * @var PowerOrderLogic
     */
    public function __construct(DtsRecordLogic $logic)
    {
        $this->logic = $logic;
    }

     /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('username','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }
    /*下单*/
    public function create($userId,$data){

        $rule = $this->app(SysGameRuleService::class)->getQuery()->where('id',$data['rule_id'])->first();
        if (!$rule) {
            throw new AppException('玩法不存在',400);
        }
        if ($rule->game_id != $data['game_id']) {
            throw new AppException('游戏不存在',400);
        }

        Db::beginTransaction();
        try {
            $record['user_id'] = $userId;
            $record['rule_id']  = $rule['id'];
            $record['game_id']  = $rule['game_id'];
            $record['order_sn'] = $this->makeOrdersn('GM');
            $record['order_number'] =  $data['total_nums'];
            $record['order_amount'] =  $data['total_money'];
            $record['order_content'] = $data['codes'];
            //创建
            $order = $this->logic->create($record);

            Db::commit();

            return $order;

        } catch(\Throwable $e){

            Db::rollBack();

            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[11111]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }
    /*支付*/
    public function pay($userId,$payType,$orderId){
        $order = $this->logic->findWhere(['user_id'=>$userId,'order_id'=>$orderId]);
        if (!$order) {
            throw new AppException('订单不存在',400);
        }
        if ($order->order_paid == 1) {
            throw new AppException('订单已完成',400);
        }

        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($order['order_amount']) > $balance[strtolower($payType)]){
            throw new AppException($payType.'余额不足',400);
        }

        Db::beginTransaction();
        try {

            $record['order_paid'] = 1;
            $record['order_coin'] = strtolower($payType);
            //创建
            $res = $this->logic->update($order->order_id,$record);
            if(!$res){
                throw new \Exception('更新失败');
            }

            $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($record['order_coin']),$balance[$record['order_coin']],-$order['order_amount'],6,'投注扣除',$order->id);
            if($rel !== true){
                throw new \Exception('更新资产失败');
            }
            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();

            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[11111]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*开奖*/
    public function give($orderId,$give_content){
        $order = $this->logic->find($orderId);
        if (!$order) {
            throw new AppException('订单不存在',400);
        }
        if ($order->give_time > 0) {
            throw new AppException('订单已开奖',400);
        }

        Db::beginTransaction();
        try {

            $record['give_content'] = $give_content;
            $record['give_time'] = time();
            //创建
            $res = $this->logic->getQuery()->where(['order_id'=>$order->order_id,'give_time'=>0])->update($record);
            if(!$res){
                throw new \Exception('更新失败');
            }
            
            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[11111]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*结算*/
    public function settle($orderId){
        $order = $this->logic->find($orderId);
        if (!$order) {
            throw new AppException('订单不存在',400);
        }
        if ($order->settle_time > 0) {
            throw new AppException('订单已结算',400);
        }

        $settle_amount = bcmul($order->order_amount,$order->settle_rate,6);

        Db::beginTransaction();
        try {

            $record['settle_amount'] = $settle_amount;
            $record['settle_time'] = time();
            //创建
            $res = $this->logic->getQuery()->where(['order_id'=>$order->order_id,'settle_time'=>0])->update($record);
            if(!$res){
                throw new \Exception('更新失败');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,strtolower($record['order_coin']),$balance[$record['order_coin']],$record['settle_amount'],7,'投注结算');
            if($rel !== true){
                throw new \Exception('更新资产失败');
            }
            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[11111]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }


}