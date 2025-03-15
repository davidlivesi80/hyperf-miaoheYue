<?php


namespace App\Common\Service\Users;

use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysExchangeService;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserBalanceLogic;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;

class UserBalanceService extends BaseService
{
    /**
     * @var UserBalanceLogic
     */
    public function __construct(UserBalanceLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllType()
    {
        return [
            ['id'=>0,'title'=>'系统导入'],

            ['id'=>1,'title'=>'额外奖励'],

            ['id'=>2,'title'=>'在线充值'],

            ['id'=>3,'title'=>'在线提现'],

            ['id'=>4,'title'=>'在线互转'],

            ['id'=>5,'title'=>'在线兑换'],

            ['id'=>6,'title'=>'秒合约交易跟单'],

            ['id'=>7,'title'=>'秒合约交易撤单'],

            ['id'=>9,'title'=>'秒合约平仓结算'],

            ['id'=>10,'title'=>'秒合约流水动态'],

            ['id'=>11,'title'=>'秒合约流水团队'],

            ['id'=>12,'title'=>'竞猜下单'],

            ['id'=>13,'title'=>'竞猜结算'],

            ['id'=>14,'title'=>'竞猜撤单'],

            ['id'=>15,'title'=>'购买保险'],

            ['id'=>16,'title'=>'保险赔付'],

            ['id'=>17,'title'=>'系统赔付'],

            ['id'=>18,'title'=>'体验金充值'],

            ['id'=>19,'title'=>'奖金补贴'],

            ['id'=>20,'title'=>'OTC挂单'],

            ['id'=>21,'title'=>'OTC摘单'],

            ['id'=>22,'title'=>'OTC发币'],

            ['id'=>23,'title'=>'OTC结算'],

            ['id'=>24,'title'=>'OTC撤单'],

            ['id'=>25,'title'=>'注册赠送金'],

            ['id'=>26,'title'=>'赠送金锁仓'],

            ['id'=>27,'title'=>'赠送金解锁'],

            ['id'=>28,'title'=>'赠送金到期'],
        ];
    }

    public function getType($type)
    {
        switch ($type) {
            case 0:
                return '系统导入';
            case 1:
                return '额外奖励';
            case 2:
                return '在线充值';
            case 3:
                return '在线提现';
            case 4:
                return '在线互转';
            case 5:
                return '在线兑换';
            case 6:
                return '秒合约交易跟单';
            case 7:
                return '秒合约交易撤单';
            case 9:
                return '秒合约平仓结算';
            case 10:
                return '秒合约流水动态';
            case 11:
                return '秒合约流水团队';
            case 15:
                return '购买保险';
            case 16:
                return '保险赔付';
            case 17:
                return '系统补偿';
            case 18:
                return '体验金充值';
            case 19:
                return '奖金补贴';
            case 20:
                return 'OTC挂单';
            case 21:
                return 'OTC摘单';
            case 22:
                return 'OTC发币';
            case 23:
                return 'OTC结算';
            case 24:
                return 'OTC撤单';
            case 25:
                return '注册赠送金';
            case 26:
                return '赠送金锁仓';
            case 27:
                return '赠送金解锁';
            case 28:
                return '赠送金到期';

        }
    }

    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }


    /**

     * 更新资产

     */
    public function rechargeTo($userId,$coin,$oldNums,$number,$type,$remark='',$sourceId="0",$targetId=0){

        Db::beginTransaction();
        try {
            $newNums = bcadd($oldNums,$number,6);
            $resuls =  $this->logic->getQuery()->where('user_id',$userId)->where(strtolower($coin), $oldNums)->update([strtolower($coin)=>$newNums]);
            if (!$resuls) throw new \Exception( "update_fail");
            $res = $this->app(UserBalanceLogService::class)->create([
                'user_id'=>$userId,
                'target_id'=>$targetId,
                'source_id'=>$sourceId,
                'coin'=>strtolower($coin),
                'old'=>$oldNums,
                'num'=>$number,
                'new'=>$newNums,
                'type'=>$type,
                'remark'=>$remark ? $remark : $this->getType($type)
            ]);
            if (!$res) throw new \Exception( "update_fail");
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            return false;
        }
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10,$sort="id",$order="desc"){

        $list = $this->logic->search($where)->with(['user'=>function($query){

                return $query->select('is_bind','email','mobile','username','id');

             }])->orderBy($sort, $order)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询日志
     */
    /**
     * 查询日志
     */
    public function logs(array $where, $page=1,$perPage = 10){

        $list = $this->app(UserBalanceLogService::class)->search($where,$page,$perPage);

        return $list;
    }

    /**创建充值*/
    public function recharge($userId,$data)
    {
        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin']);
        //开关判断
        if(!$coin->recharge_on_off){
            throw new AppException('recharge_on',400);
        }
        //时间判断
        $recharge_start_time = $this->app(SysConfigService::class)->value('recharge_start_time');
        $recharge_end_time = $this->app(SysConfigService::class)->value('recharge_end_time');
        if(time() < strtotime(date('Y-m-d ' . $recharge_start_time))  || time() > strtotime(date('Y-m-d ' . $recharge_end_time))){
            throw new AppException('recharge_time',400);//充值时间错误
        }

        return $this->app(UserRechargeService::class)->create($userId,array_merge($data,['rate'=>$coin->recharge_rate]));

    }

    /**创建提现*/
    public function withdraw($userId,$data)
    {

        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin']);
        //开关判断
        if(!$coin->withdots_on_off){
            throw new AppException('withdraw_on',400);
        }
        $is_types = $this->app(UserService::class)->find($userId);
        if($is_types->types == 3 || $is_types->is_lock >= 1){//体验用户、锁仓用户不可提现
            throw new AppException('withdraw_auto_on',400);
        }

        $self_recharge = $this->app(UserRechargeService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('recharge_status',2)->sum("order_mone");
        if(0>=$self_recharge){
            throw new AppException('withdraw_auto_on',400);
        }

        //伞下提现 + 个人提现
        $is_withdraw = $this->app(UserExtendService::class)->findByUid($userId)['is_withdraw'];
        if($is_withdraw){
            $withdraw_auto_on = 0;
        }else{
            $withdraw_auto_on = 1;
        }
        if($withdraw_auto_on){
            throw new AppException('withdraw_auto_on',400);
        }
        //数量判断
        $withdots_min_max = explode('@',$coin['withdots_min_max']);
        if($data['number'] > $withdots_min_max[1] ||$data['number'] < $withdots_min_max[0] ){
            throw new AppException('withdraw_num',400);
        }
        //时间判断
        $withdots_start_time = $this->app(SysConfigService::class)->value('withdots_start_time');
        $withdots_end_time = $this->app(SysConfigService::class)->value('withdots_end_time');
        if(time() < strtotime(date('Y-m-d ' . $withdots_start_time))  || time() > strtotime(date('Y-m-d ' . $withdots_end_time))){
            throw new AppException('withdraw_time',400);
        }
        $withdots_rate = $coin->withdots_rate;//bcmul((string)$data['number'],(string)$coin->withdots_rate,6);
        return  $this->app(UserWithdrawService::class)->create($userId,array_merge($data,['rate'=>$withdots_rate]));

    }


    /**创建互转 - 内转*/
    public function transfer($userId,$data)
    {

        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin']);
        //开关判断
        if(!$coin->transfer_on_off){
            throw new AppException('tranfer_on',400);
        }
        //时间判断
        $transfer_start_time = $this->app(SysConfigService::class)->value('transfer_start_time');
        $transfer_end_time = $this->app(SysConfigService::class)->value('transfer_end_time');
        if(time() < strtotime(date('Y-m-d ' . $transfer_start_time))  || time() > strtotime(date('Y-m-d ' . $transfer_end_time))){
            throw new AppException('tranfer_time',400);
        }
        return $this->app(UserTransferService::class)->create($userId,array_merge($data,['rate'=>$coin->transfer_rate]));

    }

    /**创建互转 - 外转*/
    public function transter($userId,$data)
    {

        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol','blue');
        //开关判断
        if(!$coin->transfer_on_off){
            throw new AppException('tranfer_on',400);
        }
        //时间判断
        $transfer_start_time = $this->app(SysConfigService::class)->value('transfer_start_time');
        $transfer_end_time = $this->app(SysConfigService::class)->value('transfer_end_time');
        if(time() < strtotime(date('Y-m-d ' . $transfer_start_time))  || time() > strtotime(date('Y-m-d ' . $transfer_end_time))){
            throw new AppException('tranfer_time',400);
        }
        return $this->app(UserTransferService::class)->found($userId,array_merge($data,['rate'=>$coin->transfer_rate]));

    }


}