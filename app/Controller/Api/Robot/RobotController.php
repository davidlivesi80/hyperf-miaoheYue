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
namespace App\Controller\Api\Robot;

use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysRobotService;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserRewardService;
use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserRobotQuickenService;
use App\Common\Service\Users\UserSportOrderService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCardsService;
use App\Common\Service\System\SysConfigService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\EmsService;
use Upp\Traits\HelpTrait;


class RobotController extends BaseController
{
    use HelpTrait;

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, UserRobotService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function info(){
        $id  = $this->request->input('id');
        $result = $this->app(UserCardsService::class)->findWith($id,['card:id,title,image,attrs']);
        return $this->success('success',$result);
    }

    /**
     * 报单-双币
     */
    public function create()
    {
        $userId = $this->request->query('userId');
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_robot_create_' . $userId)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['card_id','paysword','timer']);
        $this->validated($data, \App\Validation\Api\RobotOrderValidation::class);
        $this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        // 添加
        $res = $this->service->create($this->request->query('userId'),$data['card_id'],$data['timer']);
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }


    /*订单记录*/
    public function order()
    {
        $where['user_id'] = $this->request->query('userId');

        $where['ucard_id'] = $this->request->query('ucard_id');

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('success',$lists);
    }

    /*订单详情*/
    public function statis(){
        $id  = $this->request->input('id');
        $result =  $this->service->findWith($id,['user:id,username','ucard:id,card_id']);
         $result->ucard->card;
        return $this->success('success',$result);
    }

    /**
     * 释放记录
     */
    public function income()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_type'=>1,'ucard_id'=>$this->request->query('ucard_id','')];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRobotIncomeService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);

    }
    
     /**
     * 释放记录
     */
    public function dnamic()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_type'=>2];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRobotIncomeService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);

    }
    
    /**
     * 释放记录
     */
    public function quicken()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_types'=>[1,2]];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRobotQuickenService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);

    }

    /**
     * 燃烧信息
     */
    public function counts()
    {
        $user_id =  $this->request->query('userId');
        //总燃烧bnb
        $lists['total_bnb'] = $this->app(UserRobotService::class)->getQuery()->where('user_id',$user_id)->whereIn('status',[2,3])
            ->where('pay_type',1)->sum('total');
        //总燃烧os
        $lists['total_os'] = $this->app(UserRobotService::class)->getQuery()->where('user_id',$user_id)->whereIn('status',[2,3])
            ->where('pay_type',2)->sum('total');
        //总燃烧奖励
        $lists['total_amount'] = $this->app(UserRobotService::class)->getQuery()->where('user_id',$user_id)->whereIn('status',[2,3])
            ->sum('amount');
        //总燃烧已释放
        $lists['total_balance'] = $this->app(UserRobotService::class)->getQuery()->where('user_id',$user_id)->whereIn('status',[2,3])
            ->sum('balance');
        //总燃烧今日释放
        $lists['today_counts'] = $this->app(UserRobotIncomeService::class)->getQuery()->where('user_id',$user_id)->where('reward_type',1)
            ->where('reward_time','>=',strtotime(date("Y-m-d")))->sum('counts');

        return $this->success('success',$lists);

    }




}
