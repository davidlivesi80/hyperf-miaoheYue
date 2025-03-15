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
use App\Common\Service\System\SysVersionService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

class VersionController extends BaseController
{

    /**
     * @var SysVersionService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysVersionService $service)
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

    public function update()
    {

        $this->validated($this->request->all(), \App\Validation\Admin\VersionValidation::class);
        // 更新
        $res = $this->service->update($this->request->input('id'),$this->request->post());

        if(!$res){
            return $this->fail('更新失败');
        }

        return $this->success('更新成功');

    }


}
