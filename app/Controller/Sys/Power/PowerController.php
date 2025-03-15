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
namespace App\Controller\Sys\Power;

use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysRobotService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserPowerOrderService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserPowerIncomeService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class PowerController extends BaseController
{

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserPowerService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }


    public function lists()
    {
        $where = $this->request->inputs(['user_id','username']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    public function type()
    {

        $lists  = $this->service->getAllType();

        return $this->success('请求成功',$lists);

    }

    public function order()
    {
        $where = $this->request->inputs(['user_id','username','buy_type','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserPowerOrderService::class)->search($where,$page,$perPage);

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
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {

        return $this->fail('操作失败');

        $type = $this->request->input('type','income');

        if($type ==  "income") {
            $userPower = $this->app(UserPowerService::class)->findWhere('user_id',$id);
            $rate = $this->app(SysRobotService::class)->find(3)['rate'];
            $coin_wld = $this->app(SysCoinsService::class)->findWhere('coin_symbol','wld')['usd'];
            $res = $this->app(UserPowerOrderService::class)->income($userPower,$rate,$coin_wld);
        }elseif ($type ==  "groups"){

        }

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');

    }

    public function status ($id)
    {
        $res = $this->service->updateField($id,'status',$this->request->input('status'));

        if(!$res){
            return $this->fail('操作失败');
        }

        //清除缓存
        $this->service->cachePutMenus();

        return $this->success('操作成功');
    }

    /**
     * 释放记录
     */
    public function income()
    {

        $where = $this->request->inputs(['username','user_id','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserPowerIncomeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


}
