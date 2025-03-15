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
use App\Common\Service\System\SysCoinsService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class CoinsController extends BaseController
{

    /**
     * @var CoinsService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysCoinsService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['coin_name','coin_symbol']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists->toArray());

    }
    
    public function lista()
    {
        
        $lists  = $this->service->column([],'coin_name','coin_symbol');

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()
    {
        $data = $this->request->post();
        $this->validated($data, \App\Validation\Admin\CoinsValidation::class);
        // 添加
        $data['net_id'] = implode(',',$data['netIds']);unset($data['netIds']);
        $this->service->create($data);
        //更新缓存
        $this->service->cachePutCoins();
        return $this->success('添加成功');

    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function update($id)
    {
        $data = $this->request->inputs(['netIds','coin_type','coin_name','coin_symbol','usd','image','sort','withdots_on_off','withdots_rate','withdots_remark','withdots_min_max','withdots_num','transfer_on_off','transfer_rate','transfer_remark','transfer_min_max','recharge_on_off','recharge_rate']);
        $this->validated($data, \App\Validation\Admin\CoinsValidation::class);
        $data['net_id'] = implode(',',$data['netIds']);unset($data['netIds']);
        // 更新
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }
        //更新缓存
        $this->service->cachePutCoins();
        return $this->success('更新成功');
    }

    /**
     * 删除用户
     */
    public function remove($id){

        $this->service->remove($id);

        //更新缓存
        $this->service->cachePutCoins();

        return $this->success('删除成功');
    }



}
