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
namespace App\Controller\Api\Power;

use App\Common\Service\System\SysRobotService;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserPowerLogService;
use App\Common\Service\Users\UserPowerOrderService;
use App\Common\Service\Users\UserRelationService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\Users\UserPowerIncomeService;
use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserRobotQuickenService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Traits\HelpTrait;


class PowerController extends BaseController
{
    use HelpTrait;

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserPowerService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function info(){
        $power = $this->app(UserPowerService::class)->findByUid($this->request->query('userId'));
        $count = $this->app(UserCountService::class)->findByUid($this->request->query('userId'));
        $total_wld_usdt = bcadd((string)$power->robot_wld_usdt,(string)$power->quicken_wld_usdt,6);
        $total_wld_usdt = bcadd((string)$total_wld_usdt,(string)$power->power_wld_usdt,6);
        $total_wld_usdt = bcadd((string)$total_wld_usdt,(string)$power->total_wld_usdt,6);
        $reward_wld_surplus = bcsub($count->self_usdt,(string)$total_wld_usdt,6);
        $reward_atm_surplus = bcsub($count->self_atm,(string)$power->total_atm,6);
        $result['info']  = $power;
        $result['is_active'] = $reward_wld_surplus > 0 ? 1 : 0;
        $result['is_atmive'] = $reward_atm_surplus > 0 ? 1 : 0;
        $result['rate'] = $this->app(SysRobotService::class)->find(3)['rate'];
        return $this->success('success',$result);
    }

    public function robot()
    {
        $result['info'] = $this->app(SysRobotService::class)->find(3);
        $result['atm_price'] = $this->app(SysCoinsService::class)->getField('coin_symbol','atm','usd');
        $result['wld_price'] = $this->app(SysCoinsService::class)->getField('coin_symbol','wld','usd');
        return $this->success('success',$result);
    }

    /**
     * 报单
     */
    public function create()
    {
//        $power_switch =  $this->app(SysConfigService::class)->value('power_switch');
//        if($power_switch == 0){
//            return $this->fail('Not open yet!');
//        }

        if(!$this->limitIp($this->request->query('ip'),'ip_lock_power_create')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['series','number','paysword','paid']);
        $this->validated($data, \App\Validation\Api\PowerValidation::class);
        // 添加
        $res = $this->app(UserPowerOrderService::class)->create($this->request->query('userId'),$data['number'],$data['series'],$data['paid'],1);
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }

    /**
     * 取消
     */
    public function found()
    {
//        $power_switch =  $this->app(SysConfigService::class)->value('power_switch');
//        if($power_switch == 0){
//            return $this->fail('Not open yet!');
//        }
       
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_power_create')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['series','number','paysword','paid']);
        $this->validated($data, \App\Validation\Api\PowerValidation::class);
        // 添加
        $res = $this->app(UserPowerOrderService::class)->found($this->request->query('userId'),$data['number'],$data['series'],$data['paid'],2);
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }

    /*订单记录*/
    public function order()
    {
        $where= ['user_id'=>$this->request->query('userId'),'buy_type'=>$this->request->query('type',2)];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserPowerOrderService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);
    }

    /**
     * 释放记录
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
     * 统计信息
     */
    public function counts()
    {
        $user_id =  $this->request->query('userId');

        $lists['power_total'] = $this->app(UserPowerService::class)->getQuery()->where('power','>',0)->sum("power");

        $lists['power_today'] = $this->app(UserPowerOrderService::class)->getQuery()->whereIn('status',[2])->whereDate('buy_time',date('Y-m-d'))->sum("total");
        
        $lists['power_self'] = $this->app(UserPowerService::class)->findByUid($user_id)['power'];
        
        $lists['reward_today'] = $this->app(UserBalanceLogService::class)->getQuery()->where('user_id',$user_id)->whereDate('created_at',date('Y-m-d'))->where('coin','oss')->where('type',12)->sum("num");
        
        $lists['reward_total'] = $this->app(UserBalanceLogService::class)->getQuery()->where('user_id',$user_id)->where('coin','oss')->where('type',12)->sum("num");
  
        return $this->success('success',$lists);

    }



}
