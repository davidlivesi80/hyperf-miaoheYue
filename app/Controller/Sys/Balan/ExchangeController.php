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
namespace App\Controller\Sys\Balan;

use Upp\Basic\BaseController;
use App\Common\Service\System\SysExchangeService;
use App\Common\Service\Users\UserExchangeService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class ExchangeController extends BaseController
{

    /**
     * @var CardService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysExchangeService $service)
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

    /**
     * 添加
     */

    public function create()
    {
        $data = $this->request->inputs(['give_coin','paid_coin','price','rate','min_num','max_num','sort']);

        $this->validated($data, \App\Validation\Admin\ExchangeValidation::class);

        //检测
        $info = $this->service->check($data['give_coin'],$data['paid_coin']);
        if($info){
            return $this->fail('交易已添加');
        }

        // 添加
        $res = $this->service->create($data);

        if(!$res){
            return $this->fail('添加失败');
        }

        return $this->success('添加成功');

    }

    /**
     * 更新
     */

    public function update()
    {
        $data = $this->request->inputs(['give_coin','paid_coin','price','rate','min_num','max_num','sort']);

        $this->validated($this->request->all(), \App\Validation\Admin\ExchangeValidation::class);

        // 更新
        $res = $this->service->update($this->request->input('id'),$data);

        if(!$res){
            return $this->fail('更新失败');
        }

        return $this->success('更新成功');

    }


    /**
     * 删除
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


    public function order(){

        $where = $this->request->inputs(['username','exchange','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserExchangeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    
}
