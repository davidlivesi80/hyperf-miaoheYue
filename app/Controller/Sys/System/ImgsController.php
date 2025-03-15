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

use Upp\Basic\BaseController;
use App\Common\Service\System\SysImgsService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class ImgsController extends BaseController
{

    /**
     * @var SysImgsService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysImgsService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {

        $where = $this->request->inputs(['type','title']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);


    }


    public function types()
    {

        $lists  = $this->service->getAllType();

        return $this->success('请求成功',$lists);

    }


    public function method()
    {

        $lists  = $this->service->getAllMethod();

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()

    {
        $this->validated($this->request->all(), \App\Validation\Admin\ImgsValidation::class);

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
        
        $data = $this->request->inputs(['method','type','title','image','url','lang','sort']);
         
        $this->validated($data, \App\Validation\Admin\ImgsValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

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
    
    /**
     * 批量删除用户
     */
    public function batch(){

        $res = $this->service->batch($this->request->input('ids'));

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


}
