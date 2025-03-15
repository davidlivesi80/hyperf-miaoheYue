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
namespace App\Controller\Sys\Robot;


use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserRewardService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserService;
use App\Common\Service\System\SysConfigService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class RobotController extends BaseController
{

    /**
     * @var UserRobotService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, UserRobotService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }


    public function order()
    {

        $where= $this->request->inputs(['status','user_id','robot_id','username','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserRobotService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function orderCreate()
    {
        $data = $this->request->inputs(['total','username']);

        $this->validated($data, \App\Validation\Admin\RobotOrderValidation::class);
        $user = $this->app(UserService::class)->findWhere('username',$data['username']);
        // 添加
        $adminId = $this->request->input('adminId');
        if(!$adminId){
            return $this->fail('管理ID错误');
        }

        $res = $this->app(UserRobotService::class)->create($user->id,$data['total'],1,2,$adminId);

        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function orderUpdate($id)
    {
        $type = $this->request->input('type','income');
        if($type ==  "cancel"){
            $res =$this->app(UserRobotService::class)->find($id);
            $res = $this->app(UserRobotService::class)->cancel($res);
        }elseif ($type ==  "income"){
             $order = $this->app(UserRobotService::class)->find($id);
             $res = $this->app(UserRobotService::class)->income($order);
        }elseif ($type ==  "groups"){
            $res =$this->app(UserRobotIncomeService::class)->find($id);
            $groupsPins = $this->app(SysConfigService::class)->value('groups_pins');
            $groupRate = $this->app(SysConfigService::class)->value('groups_rate');
            $groupRateArr = explode('@',$groupRate);
            $res = $this->app(UserRobotService::class)->groups($res, $groupRateArr,$groupsPins);
        }else{
            return $this->fail('操作错误');
        }
        return $this->success('操作成功',$res);
    }

    /**
     * 释放记录
     */
    public function income()
    {

        $where = $this->request->inputs(['username','user_id','order_id','timeStart','timeEnd']);
        
        $where['reward_type'] = 1;

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRobotIncomeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 收益统计
     */
    public function reward()
    {

        $where = $this->request->inputs(['username','user_id']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRewardService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


}
