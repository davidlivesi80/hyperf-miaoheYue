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
namespace App\Controller\Sys\System;


use Hyperf\DbConnection\Db;
use Upp\Basic\BaseController;
use App\Common\Service\System\SysCrontabService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Traits\HelpTrait;


class CrontabController extends BaseController
{
    use HelpTrait;
    /**
     * @var SysCrontabService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysCrontabService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }


    public function lists()
    {
        $where = $this->request->inputs(['type']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()
    {

        $this->validated($this->request->all(), \App\Validation\Admin\CrontabValidation::class);

        $this->service->checkName($this->request->input('task_name'));

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            return $this->fail('添加失败');
        }

        return $this->success('添加成功');

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {

        $this->validated($this->request->post(), \App\Validation\Admin\CrontabValidation::class);

        // 更新
        $res = $this->service->update($id,$this->request->post());

        if(!$res){
            return $this->fail('更新失败');
        }

        return $this->success('更新成功');

    }

    /**
     * 删除用户
     */
    public function remove($id){

        $res = $this->service->remove($id);

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

        return $this->success('操作成功');
    }


    /**
     * 删除用户
     */
    public function clear(){

        $type = $this->request->input('type');

        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if (!$powerInsert){
            $this->fail('今日控制未开启');
        }

        if($type == "upgrade"){
            $orderCache = $this->getCache()->get('upgrade_'.date('Y-m-d'));
            if($orderCache){
                $this->fail('任务正在执行');
            }
            Db::table('sys_power')->where('id',$powerInsert->id)->update(['upgrade_time'=>0]);
        }

        return $this->success('操作成功');
    }


}
