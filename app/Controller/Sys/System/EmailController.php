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
use Upp\Service\EmsService;
use App\Common\Service\System\SysEmailService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class EmailController extends BaseController
{

    /**
     * @var SysEmailService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysEmailService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }


    public function lists()
    {
        $where = $this->request->inputs(['username']);

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

        // 获取通过验证的数据...
        $this->validated($this->request->all(),\App\Validation\Admin\EmailValidation::class);

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            return $this->fail('添加失败');
        }

        $this->service->cachePutEmail();

        return $this->success('添加成功');

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {
        
        $data = $this->request->inputs(['host','port','address','username','password','encryption']);

        $this->validated($data, \App\Validation\Admin\EmailValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fail('更新失败');
        }

        $this->service->cachePutEmail();

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

        $this->service->cachePutEmail();

        return $this->success('操作成功');
    }


    public function send ($id)
    {
        $emailServer = $this->service->find($id);

        if(!$emailServer){
            return $this->fail('操作失败');
        }
        $email = $this->request->input('email');
        if(!$email){
            return $this->fail('邮箱错误');
        }

        $result = $this->app(EmsService::class)->send("sms_tip",$this->request->input('email'),$emailServer);

        if($result !== true){
            return $this->fail('发送失败：'.$result);
        }

        return $this->success('操作成功');
    }


}
