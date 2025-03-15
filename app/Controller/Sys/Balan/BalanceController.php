<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller\Sys\Balan;

use App\Common\Model\Users\UserBalance;
use App\Common\Model\Users\UserRecharge;
use App\Common\Service\Rabc\UsersService;
use PragmaRX\Google2FA\Google2FA;
use Upp\Basic\BaseController;
use Upp\Service\ExportService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\{UserService,
    UserBalanceLogService,
    UserBalanceService,
    UserTransferService,
    UserRechargeService,
    UserSafetyOrderService,
    UserExtendService,
    UserWithdrawService};
use App\Common\Service\System\SysCoinsService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Traits\HelpTrait;

class BalanceController extends BaseController
{
    use HelpTrait;
    /**
     * @var UserBalanceService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserBalanceService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['uname']);
        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误：' . $e->getMessage());
        }

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $sort = $this->request->input('sort','id');

        $order = $this->request->input('order','desc');

        $lists  = $this->service->search($where,$page,$perPage,$sort,$order);

        return $this->success('请求成功',$lists);

    }
    
    /**
     * 导出
    */
    public function exports()
    {
        //set_time_limit(0);
        
        ini_set('memory_limit', '1024M');
        
        $list = $this->service->searchApi([])->toArray();
        
        $data = [];
        
        foreach ($list as $value) {
            $data[] = [
                'uid'=> $value['user_id'],
                'username'=> $value['user']['username'],
            ];
        }
        
        return $this->success('导出成功',$data);
      
    }

    public function type()
    {

        $lists  = $this->service->getAllType();

        return $this->success('请求成功',$lists);

    }

    public function logs()
    {

        $where = $this->request->inputs(['user_id','username','type','coinames','remark','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->logs($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }
    
    /**
     * 导出
    */
    public function logsExports()
    {
        set_time_limit(0);
        
        ini_set('memory_limit', '1024M');
        
        $where = $this->request->inputs(['user_id','username','type','coinames','timeStart','timeEnd']);
        
        $list = $this->app(UserBalanceLogService::class)->searchApi($where)->toArray();
        
        $data = [];
        
        foreach ($list as $value) {
            $data[] = [
                'id'=>$value['id'],
                'username'=>$value['user']['username'],
                'coin'=>$value['coin'],
                'num'=>$value['num'],
                'remark'=>$value['remark']
            ];
        }
        
        return $this->success('导出成功',$data);
      
    }

    /**
     * 更新资产
     */
    public function wallet($id)
    {

        //return $this->fail('已关闭！');
        $data = $this->request->post();
        $this->validated($this->request->post(), \App\Validation\Admin\WalletValidation::class);
        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin']);
        $data['order_type'] = $this->request->input('order_type') > 1 ? $this->request->input('order_type') : 1;
        $data['remark'] = "系统充值";
        if($data['order_type'] == 17){
            if(0 >= $data['number']){
                return $this->fail('只能大于0');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($id);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($id,strtolower($data['coin']),$balance[strtolower($data['coin'])],$data['number'],1,'系统补贴');
            if($res !== true){
                return $this->fail('系统失败');
            }
        }if($data['order_type'] == 19){
            if(0 >= $data['number']){
                return $this->fail('只能大于0');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($id);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($id,strtolower($data['coin']),$balance[strtolower($data['coin'])],$data['number'],19,'奖金补贴');
            if($res !== true){
                return $this->fail('系统失败');
            }
        }if($data['order_type'] == 25){
            if(0 >= $data['number']){
                return $this->fail('只能大于0');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($id);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($id,strtolower($data['coin']),$balance[strtolower($data['coin'])],$data['number'],25,'注册赠送金');
            if($res !== true){
                return $this->fail('系统失败');
            }
        }elseif($data['order_type'] == 1){
            if(0 >= $data['number']){
                $balance = $this->app(UserBalanceService::class)->findByUid($id);
                if(abs($data['number']) > $balance[strtolower($data['coin'])]){
                    return $this->fail('余额不足');
                }
                $res =  $this->app(UserBalanceService::class)->rechargeTo($id,strtolower($data['coin']),$balance[strtolower($data['coin'])],$data['number'],1,'系统扣除');
                if($res !== true){
                    return $this->fail('扣除失败');
                }
            }else{
                $this->app(UserRechargeService::class)->create($id,array_merge($data,['rate'=>$coin->recharge_rate]));
            }

        }


        return $this->success('更新成功');

    }
    
    /**
     * 更新线上资产
     */
    public function transfer($username)
    {

        $where = $this->request->inputs(['user_id','username','coinames','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserTransferService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }
    
    /**
     * 导出
    */
    public function transferExports()
    {
        //set_time_limit(0);
        ini_set('memory_limit', '1024M');
        
        $where = $this->request->inputs(['user_id','username','coinames','timeStart','timeEnd']);
        
        $list = $this->app(UserTransferService::class)->searchExp($where)->toArray();
        
        $data = [];
        
        foreach ($list as $value) {
            if($value['transfer_status'] == 2){
                $transfer_status = '已完成';
            }else {
                $transfer_status = '待处理';
            }
            $data[] = [
                'id'=>$value['order_id'],
                'username'=> $value['user']['username'],
                'target'=>$value['target']['username'],
                'coin'=>$value['order_coin'],
                'mone'=>$value['order_mone'],
                'status'=>$transfer_status
            ];
        }
       
        
        return $this->success('导出成功',$data);
      
    }

    /**
     * 导出充值
     */
    public function recharge(){

        $where = $this->request->inputs(['uname','account','type','status','address','coinames','timeStart','timeEnd']);
        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误：' . $e->getMessage());
        }
        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRechargeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 导出充值
     */
    public function RechargeExports()
    {
        //set_time_limit(0);

        ini_set('memory_limit', '1024M');

        $where = $this->request->inputs(['uname','account','type','status','coinames','timeStart','timeEnd']);

        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $list = $this->app(UserRechargeService::class)->searchExp($where)->toArray();

        $data = [];

        foreach ($list as $value) {
            if($value['recharge_status'] == 2){
                $recharge_status = '已完成';
            }else {
                $recharge_status = '待处理';
            }
            $data[] = [
                'id'=>$value['order_id'],
                'sn'=>$value['order_sn'],
                'username'=>$value['user']['username'],
                'coin'=>$value['order_coin'],
                'amount'=>$value['order_amount'],
                'mone'=>$value['order_mone'],
                'created_at'=>$value['created_at'],
                'status'=>$recharge_status
            ];
        }

        return $this->success('导出成功',$data);

    }


    /**
     * 更新资产
     */
    public function rechargeYes($id)
    {

        $this->app(UserRechargeService::class)->confirm($id);

        return $this->success('操作成功');

    }

    /**
     * 更新资产
     */
    public function rechargeNos($id)
    {

        $this->app(UserRechargeService::class)->cancel($id,'后台取消');

        return $this->success('操作成功');

    }

    /**
     * 更新资产
     */
    public function rechargeUps($id)
    {

        $this->app(UserRechargeService::class)->finishUps($id,'后台上分');

        return $this->success('操作成功');

    }

    public function withdrawRules(){


        $lists['usdt'] = $this->app(SysConfigService::class)->value('withdraw_audot_usdt');

        $lists['wld'] = $this->app(SysConfigService::class)->value('withdraw_audot_wld');

        $lists['atm'] = $this->app(SysConfigService::class)->value('withdraw_audot_atm');

        return $this->success('请求成功',$lists);
    }

    public function withdraw(){

        $where = $this->request->inputs(['uname','account','coinames','address','type','status','timeStart','timeEnd']);

        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserWithdrawService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    public function walletBalance(){

        $lists = $this->app(UserService::class)->walletBalance();

        return $this->success('请求成功',$lists);
    }



    public function withdrawOrderNewId() {
        $order_id = $this->app(UserWithdrawService::class)->getQuery()->where('withdraw_status', 1)->orderBy('order_id', 'desc')->value('order_id');
        return $this->success('请求成功', $order_id);
    }
    
    /**
     * 导出提现
    */
    public function withdrawExports()
    {
        //set_time_limit(0);
        
        ini_set('memory_limit', '1024M');

        $where = $this->request->inputs(['uname','account','coinames','type','status','timeStart','timeEnd']);

        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }
        
        $list = $this->app(UserWithdrawService::class)->searchExp($where)->toArray();

        
        $data = [];
        
        foreach ($list as $value) {
            if($value['withdraw_status'] == 2){
                $withdraw_status = '已完成';
            }else {
                $withdraw_status = '待处理';
            }
            $data[] = [
                'id'=>$value['order_id'],
                'sn'=>$value['order_sn'],
                'username'=>$value['user']['username'],
                'coin'=>$value['order_coin'],
                'address'=>$value['bank_account'],
                'amount'=>$value['order_amount'],
                'mone'=>$value['order_mone'],
                'created_at'=>$value['created_at'],
                'status'=>$withdraw_status
            ];
        }
      
        return $this->success('导出成功',$data);
      
    }

    /**
     * 更新资产
     */
    public function withdrawYes($id)
    {
       


        $this->app(UserWithdrawService::class)->confirm($id,$this->request->input('tx'),"");

        return $this->success('操作成功');

    }

    /**
     * 更新资产
     */
    public function withdrawAud($id)
    {
        $ip_lock =  $this->getCache()->get('adm_lock_withdraw_create');
        if($ip_lock){
            return $this->fail('操作频繁，稍后尝试!');
        }
        $this->getCache()->set('adm_lock_withdraw_create', 10, 20);

        $code = $this->request->input('code','');

        if(empty($code) || $code== ""){
            return $this->fail('失败');
        }

        $res = $this->app(UserWithdrawService::class)->audit($id,$code);
        if(!$res){
            return $this->fail('失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 更新资产
     */
    public function withdrawNos($id)
    {

        $this->app(UserWithdrawService::class)->cancel($id,"",$this->request->input('detail'));

        return $this->success('操作成功');

    }
    
     /**
     * 更新资产
     */
    public function withdrawReset($id)
    {

        $this->app(UserWithdrawService::class)->getQuery()->where('order_id',$id)->update(['withdraw_status'=>1,'audot_time'=>0]);
        
        return $this->success('操作成功');

    }

}
