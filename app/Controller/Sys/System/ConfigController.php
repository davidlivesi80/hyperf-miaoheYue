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
use App\Common\Service\System\SysConfigService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class ConfigController extends BaseController
{
    /**
     * @var SysConfigService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysConfigService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    /**
     * 站点配置
     */
    public function lists()
    {

        $where= $this->request->inputs(['types']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 站点配置
     */
    public function keys()
    {

        $keys = $this->request->input('keys');

        $lists  = $this->service->value($keys);

        return $this->success('请求成功',$lists);

    }

    public function types()
    {

        $lists  = $this->service->getAllType();

        return $this->success('请求成功',$lists);

    }

    public function eles()
    {

        $lists  = $this->service->getAllEles();

        return $this->success('请求成功',$lists);
    }

    /**
     * 添加配置
     */

    public function create()

    {

        $this->validated($this->request->all(), \App\Validation\Admin\ConfigValidation::class);

        $this->service->checkKeys($this->request->input('key'));

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            return $this->fail('添加失败');
        }
        //更新缓存
        $this->service->cachePutConfig();

        return $this->success('添加成功',[]);
    }

    /**
     * 修改配置
     */

    public function update($id)
    {
        $data = $this->request->inputs(['name','key','options','types','element','sort']);

        $this->validated($data, \App\Validation\Admin\ConfigValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fail('更新失败');
        }
        //更新缓存
        $this->service->cachePutConfig();

        return $this->success('更新成功',[]);

    }

    /**
     * 配置赋值
     */

    public function valued($id)
    {
        $data = $this->request->inputs(['value']);

        $this->validated($data, \App\Validation\Admin\ValuedValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fail('更新失败');
        }

        //更新缓存
        $this->service->cachePutConfig();

        return $this->success('更新成功',[]);

    }

    /**
     * 删除配置
     */
    public function remove($id){

        $res = $this->service->remove($id);

        if(!$res){
            return $this->fail('操作失败');
        }

        //更新缓存
        $this->service->cachePutConfig();

        return $this->success('操作成功',[]);
    }

}
