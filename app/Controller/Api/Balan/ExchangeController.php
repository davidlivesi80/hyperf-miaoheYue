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
namespace App\Controller\Api\Balan;

use Upp\Basic\BaseController;
use Upp\Service\EmsService;
use App\Common\Service\Users\{UserExchangeService, UserService};
use App\Common\Service\System\SysExchangeService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class ExchangeController extends BaseController
{

    /**
     * @var PowerService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysExchangeService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }


    public function lists()
    {

        $lists  = $this->service->searchApi(['status'=>1]);

        return $this->success('请求成功',$lists);

    }

    public function info($id){

        $result = $this->service->searchOne($id);

        if(!$result){
            return $this->fail('数据不存在');
        }

        return $this->success('请求成功',$result);
    }

    /**
     * 下单
     */
    public function create()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_exchange')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['exchange_id','number','code']);
        //数据验证
        $this->validated($data,\App\Validation\Api\AddsExchangeValidation::class);
        //验证短信
        $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code']);
        //检测交易
        $excheng = $this->service->find($data['exchange_id'] ?? 0);
        if(!$excheng){
            return $this->fail('交易不存在');
        }
        //创建订单
        $order = $this->app(UserExchangeService::class)->create($this->request->query('userId'),$excheng,$data['number']);
        if(!$order){
            return $this->fail('交易失败');
        }
        return $this->success('提交成功',['orderId'=>$order->order_id]);

    }


    /**
     * 订单状态
     */
    public function remove($id)
    {
        //订单信息
        $order =  $this->app(UserExchangeService::class)->cancel($this->request->query('userId'),$id);
        if(!$order){
            return $this->fail('订单不存在');
        }
        return $this->success('获取成功',$order);
    }

    /**
     * 订单列表
     */
    public function order()
    {

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserExchangeService::class)->searchApi($this->request->query('userId'),$page,$perPage);

        return $this->success('获取成功',$lists);

    }

    /**
     * 统计
     */
    public function counts($id)
    {

        $hourCount = $this->app(UserExchangeService::class)->searchSum(['exchange_id'=>$id,'timeStart'=>date('Y-m-d H:i:s',(time() - 86400)),'timeEnd'=>date('Y-m-d H:i:s')]);

        $weekCount = $this->app(UserExchangeService::class)->searchSum(['exchange_id'=>$id,'timeStart'=>date('Y-m-d H:i:s',(time() - 432000)),'timeEnd'=>date('Y-m-d H:i:s')]);

        $newsOrder = $this->app(UserExchangeService::class)->searchNew(['exchange_id'=>$id]);

        return $this->success('获取成功', compact("hourCount","weekCount",'newsOrder'));

    }


}
