<?php


namespace App\Common\Service\Users;

use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysContractService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserWithdrawLogic;
use Upp\Exceptions\AppException;
use Upp\Service\BnbService;
use Upp\Service\SignService;


class UserWithdrawService extends BaseService
{
    /**
     * @var UserWithdrawLogic
     */
    public function __construct(UserWithdrawLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('is_bind','email','mobile','username','id')->with('extend:user_id,is_withdraw,is_duidou','counts:user_id,recharge,withdraw','reward:user_id,income,deficit,safety');

        }])->paginate($perPage,['*'],'page',$page);

        $now = Carbon::now();$start = $now->startOfDay()->timestamp;$ends  = $now->endOfDay()->timestamp;

        $list->each(function ($item) use ($start,$ends){
            $qudao = $this->app(UserService::class)->getQudaoByUser($item['user_id'],false);
            if($qudao){
                $item['account'] =  $qudao;
            }else{
                $item['account'] =   "";
            }
            $item['recharge'] =   $item['user']['counts']['recharge'];
            $item['withdraw'] =  $item['user']['counts']['withdraw'];
            $lirun = bcadd((string)$item['user']['reward']['income'] ,(string)$item['user']['reward']['safety'],2);
            $item['lirun'] = bcsub((string)$lirun,(string)$item['user']['reward']['deficit'],2);
            //$item['safety'] = $item['user']['reward']['safety'];
            $income_today = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$item['user_id'])->where('reward_time','>=',$start)->where('reward_time','<=',$ends)->where('reward_type',1)->sum('reward');
            $deficit_today = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$item['user_id'])->where('reward_time','>=',$start)->where('reward_time','<=',$ends)->where('reward_type',2)->sum('reward');;
            $item['lirun_today'] = bcsub((string)$income_today,(string)$deficit_today,2);
            return $item;
        });

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){
            return $query->select('username','id')->with('extend:user_id,is_withdraw,is_duidou');

        }])->paginate($perPage,['*'],'page',$page);


        return $list;

    }

    /**
     * 查询构造-导出
     */
    public function searchExp(array $where){
        $list = $this->logic->search($where)->with(['user:id,username'])->get();
        return $list;
    }

    /**
     * 添加
     */
    public function create($userId,$data){

        $exists = $this->logic->getQuery()->where('user_id',$userId)->where('withdraw_status',1)->exists();
        if ($exists) {
            throw new AppException('order_tip',400);//等待连上确认上一笔，请等待
        }

        // 判断前缀
        if ($data['series'] == 4  && strtolower(substr($data['address'],0,1)) != 't') {
            throw new AppException('the_receiving_address_does_not_match_the_selected_network',400);//接收地址和所选的网络不匹配
        }
        if (in_array($data['series'],[3]) && strtolower(substr($data['address'],0,2)) != '0x') {
            throw new AppException('the_receiving_address_does_not_match_the_selected_network',400);//接收地址和所选的网络不匹配
        }
        // 判断接收地址长度
        if ($data['series'] == 4 && strlen($data['address']) != 34) {
            throw new AppException('the_receiving_address_length_is_not_34_bits',400);//接收地址长度不是34位
        }
        if (in_array($data['series'],[3])  && strlen($data['address']) != 42) {
            throw new AppException('the_receiving_address_length_is_not_42_bits',400);//接收地址长度不是42位
        }

        $coinInfo = $this->app(SysCoinsService::class)->findWhere('coin_symbol',strtolower($data['coin']));
        if(!$coinInfo){
            throw new AppException('coin_parameter_error',400);
        }
        $netIds = explode(',',$coinInfo->net_id);
        if(!in_array($data['series'],$netIds)){
            throw new AppException('series_parameter_error',400);
        }
        //常规提现地址校验  标记是不是存在的地址
        $last_tree = $this->logic->getQuery()->pluck('bank_account')->toArray();
        $last_tree = array_values(array_unique($last_tree));
        $order_exist = !in_array($data['address'],$last_tree) ? 1 : 0;
        //提现地址不能是系统内部充值地址
        $address_exist = $this->app(UserService::class)->get_address_exist($data['series'],$data['address']);
        //出入奖金计算
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($data['number']) > $balance[strtolower($data['coin'])]){
            throw new AppException('insufficient_balance',400);//余额不足
        }

        Db::beginTransaction();
        try {
            $record['order_sn']   =  $this->makeOrdersn('TX');
            $record['user_id']   =  $userId;
            $record['order_amount']   = $data['number'];
            $record['remark']   =  isset($data['remark']) ? $data['remark'] : '';
            $record['order_rate']   =  $data['rate'];
            $record['order_mone']   =  bcsub((string)$data['number'],(string)($data['rate']),6);
            $record['order_total']  = bcmul( (string)$record['order_mone'],(string)$coinInfo->usd,6);
            $record['order_coin']   =  strtolower($data['coin']);
            $record['bank_account']   =  $data['address'];
            $record['order_type'] = $data['series'];
            $record['order_nei'] = $address_exist ? 1 : 0;
            $record['order_exist'] = $order_exist;
            $record['remark']   = "";
            //
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('Creation failed');//创建失败
            }
            $subNum = bcmul((string)$order->order_amount,'-1',6);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_coin,$balance[$order->order_coin],$subNum,3,'奖金提取',$order->order_id);
            if($res !== true){
                throw new \Exception('Asset failed');//资产失败
            }
            Db::commit();

            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[提现交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }
    }

    public function confirm($id,$pay_hash="",$detail=""){

        $order = $this->logic->getQuery()->where('order_id',$id)->first();
        if (!$order) {
            throw new AppException('订单不存在',400);
        }
        if(empty($pay_hash)){
            throw new AppException('哈希不能为空',400);
        }

        if ( $order->withdraw_status != 3) {
            throw new AppException('订单已处理',400);
        }

        $data['withdraw_status']   =  2;
        $data['detail']   =  $detail;
        $data['pay_hash'] =  $pay_hash;
        $res = $this->logic->update($id,$data);
        if(!$res){
            return false;
        }
        $this->app(UserCountService::class)->getSelfYeji($order->user_id);
        return true;
    }

    public function cancel($id, $pay_hash="",$detail = '驳回取消'){

        $order = $this->logic->getQuery()->where('order_id',$id)->first();

        if (!$order) {
            throw new AppException('订单不存在',400);
        }

        if ($order->withdraw_status != 3 && $order->withdraw_status != 1 ) {
            throw new AppException('订单已处理',400);
        }

        Db::beginTransaction();
        try {

            $data['withdraw_status']   =  0;
            $data['detail']  =  $detail;
            $data['pay_hash'] =  $pay_hash;
            //删除返还
            if($this->logic->update($id,$data)){
                $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
                $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_coin,$balance[strtolower($order->order_coin)],$order->order_amount,3,'提现返还',$order->order_id);
            }
            Db::commit();
            $this->app(UserCountService::class)->getSelfYeji($order->user_id);
            return true;

        } catch(\Throwable $e){

            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[提现交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }

    // 审核
    public function audit($id,$code)
    {
        // 查提现记录
        $info = $this->logic->getQuery()->where('order_id',$id)->where('withdraw_status',1)->first();
        if (!$info) {
            throw new AppException('订单已处理',400);
        }
        $userExtend = $this->app(UserExtendService::class)->findByUid($info->user_id);
        if(!$userExtend->is_withdraw){
            throw new AppException('该用户提现状态关闭',400);
        }

        if ($info->order_type == 3){
            $series_id = 1;
        }elseif ($info->order_type == 5){
            $series_id = 2;
        }elseif ($info->order_type == 4){
            $series_id = 3;
        }elseif ($info->order_type == 6){
            $series_id = 4;
        }else{
            throw new AppException('通道错误',400);
        }
        $data = [
            'id'=>$info->order_id,
            'user_id'=>$info->user_id,
            'series_id'=>$series_id,
            'amount'=>$info->order_mone,
            'to'=>$info->bank_account,
            'symbol'=>$info->order_coin,
            'code'=>$code,
        ];
        try {
            $res = $this->app(UserService::class)->requestWallet('/adminapi/wallet/withdraw/audit',$data);
            $updateDta['withdraw_status']   =  3;
            $updateDta['order_key']  =  1;
            $this->logic->update($info->order_id,$updateDta);
            return true;
        } catch(\Throwable $e){
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[手动提现]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    // 审核-自动
    public function audot($id)
    {
        // 查提现记录
        $info = $this->logic->getQuery()->where('order_id',$id)->where('withdraw_status',1)->where('audot_time',0)->first();
        if (!$info) {
            throw new AppException('订单已处理',400);
        }
        $userExtend = $this->app(UserExtendService::class)->findByUid($info->user_id);
        if(!$userExtend->is_withdraw || !$userExtend->is_autodraw){
            $updateDta['audot_time']   =  time();
            $updateDta['order_key']  =  0;
            $res = $this->logic->update($info->order_id,$updateDta);
            if(!$res){
                return false;
            }
            return true;
        }
        if ($info->order_type == 3){
            $series_id = 1;
        }elseif ($info->order_type == 5){
            $series_id = 2;
        }elseif ($info->order_type == 4){
            $series_id = 3;
        }elseif ($info->order_type == 6){
            $series_id = 4;
        }else{
            throw new AppException('通道错误',400);
        }
        //大于这个金额不处理
        $withdraw_audot = $this->app(SysConfigService::class)->value('withdraw_audot_usdt');
        if($info->order_mone > $withdraw_audot){
            $updateDta['audot_time']   =  time();
            $updateDta['order_key']  =  0;
            $res = $this->logic->update($info->order_id,$updateDta);
            if(!$res){
                return false;
            }
            return true;
        }
        $data = [
            'id'=>$info->order_id,
            'user_id'=>$info->user_id,
            'series_id'=>$series_id,
            'amount'=>$info->order_mone,
            'to'=>$info->bank_account,
            'symbol'=>$info->order_coin,
        ];
        $sign = $this->app(SignService::class)->signWithd($data);
        $data['sign'] = $sign;
        try {
            $updateDta['withdraw_status']   =  3;
            $updateDta['order_key']  =  0;
            $updateDta['audot_time']   =  time();
            $this->logic->update($info->order_id,$updateDta);
            $this->app(UserService::class)->requestWallet('/adminapi/wallet/withdraw/audot',$data);
            return true;
        } catch(\Throwable $e){
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[自动提现]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }



}