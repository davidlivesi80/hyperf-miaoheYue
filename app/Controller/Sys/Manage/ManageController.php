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

namespace App\Controller\Sys\Manage;

use Upp\Basic\BaseController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Common\Service\Rabc\UsersService;
use App\Common\Service\Rabc\PowerService;

class ManageController extends BaseController
{


    /**
     * @var UsersService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UsersService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    /**

     * 权限管理|用户管理

     */
    public function index()
    {
        $adminId = $this->request->input('adminId');

        $manageInfo = $this->service->find($adminId);
        
        $manageInfo['authorities'] = $this->app(PowerService::class)->getLeftMenus($adminId);
        
        $manageInfo['roles'] = [];
        
        return $this->success('个人信息',$manageInfo);
    }

    /**
     * 权限管理|用户管理
     */

    public function lists()
    {
        $where= $this->request->inputs(['manage_name']);

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

        $this->service->checkName($this->request->input('manage_name'));

        $this->validated($this->request->all(), \App\Validation\Admin\ManageValidation::class);

        // 添加
        $res = $this->service->create($this->request->all());

        return $this->success('添加成功');



    }
    
    public function check()
    {
        $this->service->checkName($this->request->input('value'),$this->request->input('id',0));
        
        return $this->success('检测成功');
    }

    /**

     * 权限管理|用户管理@更新用户

     */

    public function update($id)

    {
        $this->service->checkName($this->request->input('manage_name'),$id);

        $data = $this->request->inputs(['manage_name','password','roleIds']);

        $this->validated($data, \App\Validation\Admin\ManageValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        return $this->success('修改成功');

    }


    /**

     * 权限管理|用户管理@修改密码

     */

    public function password()

    {
        $data = $this->request->inputs(['oldPsw','newPsw']);

        $this->validated($data, \App\Validation\Admin\PasswordValidation::class);

        // 更新
        $this->service->pass($this->request->input('adminId'),$data['oldPsw'],$data['newPsw']);

        return $this->success('修改成功');

    }
    
    /**

     * 权限管理|用户管理@重置密码

     */
    public function reset($id)
    {
        
        $res = $this->service->reset($id,'123123');
        
        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    /**

     * 权限管理|用户管理@禁用启用

     */

    public function status($id)

    {

        $res = $this->service->updateField($id,'is_disable',$this->request->input('status'));

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');

    }


    /**
     * 删除用户
     */
    public function remove($id){

        $this->service->remove($id);

        return $this->success('操作成功');
    }
    /**
     * 批量删除用户
     */
    public function batch(){

        $this->service->batch($this->request->input('ids'));

        return $this->success('操作成功');
    }

}