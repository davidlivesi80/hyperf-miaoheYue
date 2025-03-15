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
namespace App\Controller\Api\Balan;


use Hyperf\DbConnection\Db;
use PragmaRX\Google2FA\Google2FA;
use Upp\Basic\BaseController;
use Upp\Service\{EmsService};
use Upp\Traits\HelpTrait;
use App\Common\Service\Users\{UserLockedService,
    UserRechargeService,
    UserBalanceService,
    UserBankService,
    UserService,
    UserTransferService,
    UserRelationService,
    UserWithdrawService};
use App\Common\Service\System\{SysCoinsService,SysConfigService};
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


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

    /**
     * 获取资产
     */
    public function wallet()
    {
        $user = $this->app(UserService::class)->findWith($this->request->query('userId'),['balance'], ['id','username','is_lock']);
        $coins = $this->app(SysCoinsService::class)->getQuery()->orderBy('sort','desc')->select(['coin_symbol','usd','net_id'])->get()->toArray();
        $wallet_coin = [];
        $wallet_total = 0;
        //注册赠送金
        $wallet_lock = $this->app(UserLockedService::class)->searchBalance($user->id);
        foreach ($coins as $value){
            $balan['coin_symbol'] = strtoupper($value['coin_symbol']);
            $balan['balance'] = bcadd((string)$user->balance[$value['coin_symbol']],"0",6);
            $balan['balance_usd'] =  bcmul((string)$value['usd'],(string)$balan['balance'],6);
            $balan['usd']  = $value['usd'];
            $wallet_coin[] = $balan;
            $wallet_total = bcadd((string)$wallet_total,(string)$balan['balance_usd'] ,6);
        }
        $user['wallet_coin'] = $wallet_coin;

        return $this->success('success',compact('wallet_coin','wallet_total','wallet_lock'));
    }

    /**
     * 获取资产
     */
    public function info($coin)
    {
        $user = $this->app(UserService::class)->findWith($this->request->query('userId'),['balance'], ['id','username']);
        $coinInfo = $this->app(SysCoinsService::class)->findWhere('coin_symbol',strtolower($coin));

        $balance =['coin_symbol'=> strtoupper($coin),'balance'=>$user->balance[strtolower($coin)],'rate_num'=>$coinInfo->rate,'usd'=>$coinInfo->usd,
            'transfer_rate'=> $coinInfo->transfer_rate,'transfer_remark'=> $coinInfo->transfer_remark,
            'transfer_min_max'=> $coinInfo->transfer_min_max,'withdots_rate'=> $coinInfo->withdots_rate,
            'withdots_min_max'=> $coinInfo->withdots_min_max,'withdots_remark'=> $coinInfo->withdots_rate,
        ];
        return $this->success('success',$balance);
    }

    /**
     * 资产日志
     */
    public function logs()
    {

        $where['user_id'] = $this->request->query('userId');

        $where['coiname'] = $this->request->query('coiname','');

        $where['types'] =  $this->request->input('types','') ? explode(',',$this->request->input('types','')) : [];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->logs($where,$page,$perPage);

        return $this->success('success',$lists);

    }



    /**
     * 充值
     */
    public function recharge()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_recharge')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->post();
        //数据验证
        $this->validated($data,\App\Validation\Api\RechargeValidation::class);
        $data['order_type'] = 2;//手动充值
        $data['remark'] = '手动充值';
        $res = $this->service->recharge($this->request->query('userId'),$data);
        return $this->success('success');
    }

    /**
     * 充值记录
     */
    public function rechargeLogs()
    {
       
        $where['user_id'] = $userId = $this->request->query('userId');
        
        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRechargeService::class)->searchApi($where,$page,$perPage);

        return $this->success('success',$lists);

    }

    /**
     * 提现详情
     */
    public function rechargeInfo()
    {
        $where = ['user_id'=>$this->request->query('userId'),'order_id'=>$this->request->input('id')];

        $info = $this->app(UserRechargeService::class)->getQuery()->where($where)->first();

        return $this->success('success',$info);
    }

    /**
     * 提现
     */
    public function withdraw()
    {

        if(!$this->limitIp($this->request->query('ip'),'ip_lock_withdraw')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['coin','number','series','address','paysword','code','method']);
        //数据验证
        $this->validated($data,\App\Validation\Api\WithdrawValidation::class);
        //验证密码
        $this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        //验证短信,谷歌
        if($data['method']){
           $this->app(UserService::class)->checkGoole($this->request->query('userId'),$data['code']);
        }else{
            $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'other');
        }


        $res = $this->service->withdraw($this->request->query('userId'),$data);
        if(!$res){
            return $this->fail('failed');
        }
        return $this->success('success',$res);
    }

    /**
     * 提现记录
     */
    public function withdrawLogs()
    {

        $where['user_id'] =   $userId = $this->request->query('userId');
        
        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserWithdrawService::class)->searchApi($where,$page,$perPage);

        return $this->success('success',$lists);
    }
    

    /**
     * 提现详情
     */
    public function withdrawInfo()
    {
        $where = ['user_id'=>$this->request->query('userId'),'order_id'=>$this->request->input('id')];

        $info = $this->app(UserWithdrawService::class)->getQuery()->where($where)->first();

        return $this->success('success',$info);
    }


    /**
     * 互转 - 内转
     */
    public function transfer()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_transfer')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['coin','number','target','paysword','code','method']);
        //数据验证
        $this->validated($data,\App\Validation\Api\TransferValidation::class);
        //验证密码
        $this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        //验证短信,谷歌
        if($data['method']){
            $this->app(UserService::class)->checkGoole($this->request->query('userId'),$data['code']);
        }else{
            $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'other');
        }

        $res = $this->service->transfer($this->request->query('userId'),$data);
        if(!$res){
            return $this->fail('tranfer_fail');
        }
        return $this->success('success',$res);

    }


    /**
     * 互转记录
     */
    public function transferLogs()
    {
        $where = ['user_id'=>$this->request->query('userId')];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserTransferService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);

    }

    /**
     * 赠送金- 解锁
     */
    public function unlock()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_unlock',3)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $unlock = $this->app(UserLockedService::class)->unlockNum($this->request->query('userId'));
        if(0>=$unlock['reward']){
            return $this->fail('unlock_num_error'); //不可解锁
        }
        $res = $this->app(UserLockedService::class)->unlock($this->request->query('userId'),$unlock['reward'],1);
        if(!$res){
            return $this->fail('tranfer_fail');
        }
        return $this->success('success',$res);

    }




}
