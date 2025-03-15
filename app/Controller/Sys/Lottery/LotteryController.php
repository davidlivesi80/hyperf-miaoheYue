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
namespace App\Controller\Sys\Lottery;


use Upp\Basic\BaseController;
use App\Common\Service\Users\UserLotteryService;
use App\Common\Service\Users\UserLotteryIncomeService;
use App\Common\Service\Users\UserService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysLotteryService;
use App\Common\Service\System\SysLotteryAttrService;
use App\Common\Service\System\SysLotteryAttrValueService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class LotteryController extends BaseController
{

    /**
     * @var SysLotteryService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, SysLotteryService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['title','is_show','status']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    public function luck()
    {

        $lists  = $this->service->searchApi(['status'=>1]);

        return $this->success('请求成功',$lists);

    }

    /**
     * 添加产品
     */
    public function create()
    {
        $data = $this->request->all();
        $this->validated($data, \App\Validation\Admin\LotteryValidation::class);
        $data['attr_ids'] = implode(',',$data['attr_ids']);
        // 添加
        $res = $this->service->create($data);
        if(!$res){
            return $this->fails('添加失败');
        }
        //增删属性
        $this->app(SysLotteryAttrValueService::class)->create($res->id,explode(',',$data['attr_ids']));
        return $this->success('添加成功');
    }

    /**
     * 更新产品
     */
    public function update($id)
    {
        $data = $this->request->inputs(['title','image','number','price','types','attr_ids','sort']);
        $this->validated($data, \App\Validation\Admin\LotteryValidation::class);
        $data['attr_ids'] = implode(',',$data['attr_ids']);
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }
        //增删属性
        $this->app(SysLotteryAttrValueService::class)->create($id,explode(',',$data['attr_ids']));
        return $this->success('更新成功');

    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function sets($id)
    {
        // 更新
        $data = $this->request->inputs(['attr_ids','attr_value']);
        $res = $this->service->getQuery()->find($id);
        if(!$res){
            return $this->fail('更新失败');
        }
        $this->app(SysLotteryAttrValueService::class)->update($data['attr_ids'],$data['attr_value']);
        return $this->success('更新成功');
    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function status ($id)
    {
        $res = $this->service->updateField($id,'status',$this->request->input('status'));
        if(!$res){
            return $this->fail('操作失败');
        }
        //清除缓存
        return $this->success('操作成功');
    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function show ($id)
    {
        $res = $this->service->updateField($id,'is_show',$this->request->input('status'));
        if(!$res){
            return $this->fail('操作失败');
        }
        //清除缓存
        return $this->success('操作成功');
    }



    /**
     * 产品属性
     */
    public function attres()
    {

        $lists  = $this->app(SysLotteryAttrService::class)->searchApi([]);

        return $this->success('请求成功',$lists);

    }
    /**
     * 属性分组
     */
    public function attrUnit()
    {

        $lists  = $this->app(SysLotteryAttrService::class)->unit();

        return $this->success('请求成功',$lists);

    }



    /**
     * 添加产品属性
     */
    public function attrCreate()
    {
        $data = $this->request->all();
        $this->validated($data, \App\Validation\Admin\LotteryAttrValidation::class);
        // 添加
        $res = $this->app(SysLotteryAttrService::class)->create($data);

        if(!$res){
            return $this->fails('添加失败');
        }
        return $this->success('添加成功');
    }

    /**
     * 更新产品属性
     */
    public function attrUpdate($id)
    {
        $data = $this->request->inputs(['attr_name','attr_type','attr_value','attr_unit']);
        $this->validated($data, \App\Validation\Admin\LotteryAttrValidation::class);
        $res = $this->app(SysLotteryAttrService::class)->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }
        return $this->success('更新成功');

    }


    public function order()
    {

        $where= $this->request->inputs(['status','user_id','robot_id','username','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserLotteryService::class)->search($where,['user:id,username,email,mobile,is_bind','lottery:id,title,status'],$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function orderCreate()
    {
        $data = $this->request->inputs(['total','lottery_id','username']);

        $this->validated($data, \App\Validation\Admin\LotteryOrderValidation::class);
        $user = $this->app(UserService::class)->findWhere('username',$data['username']);
        // 添加
        $adminId = $this->request->input('adminId');
        if(!$adminId){
            return $this->fail('管理ID错误');
        }

        $res = $this->app(UserLotteryService::class)->create($user->id,$data['robot_id'],$data['total'],1);

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
            $res =$this->app(UserLotteryService::class)->find($id);
            $res = $this->app(UserLotteryService::class)->cancel($res);
        }elseif ($type ==  "income"){
             //$kline_key = implode(':',["btcusdt",'5min']);
             $order = $this->app(UserLotteryService::class)->find($id);
             $close_price = $this->request->input('close_price',0);
             $res = $this->app(UserLotteryService::class)->income($order,$close_price);
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

        $lists = $this->app(UserLotteryIncomeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }



}
