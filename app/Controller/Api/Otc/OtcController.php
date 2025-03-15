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

namespace App\Controller\Api\Otc;

use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserService;
use Upp\Basic\BaseController;
use App\Common\Service\Otc\{
    OtcMarketService,OtcCoinsService,OtcOrderService
};
use Upp\Traits\HelpTrait;


class OtcController extends BaseController
{
    use HelpTrait;
    /*币种列表*/
    public function coins(){

        $coins = $this->app(OtcCoinsService::class)->searchApi([]);

        return $this->success('请求成功',$coins);
    }

    /*市场列表*/
    public function market()
    {
        $where = $this->request->inputs(['otc_coin_id','side']);
        $where['status'] = [1];
        $where['finished'] = 0;
        $where['other_id'] = $this->request->query('userId');
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');

        $lists  = $this->app(OtcMarketService::class)->searchApi($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /*发布*/
    public function publish()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_publish')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['coin_id','side','number','price','code']);

        $this->validated($data, \App\Validation\Api\OtcPubValidation::class);
        //验证短信
        $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code']);
        // 发布
        $res = $this->app(OtcMarketService::class)->publish($this->request->query('userId'),$data);
        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('发布成功',$res);
    }

    /*下单*/
    public function poplish()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_poplish')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }

        $data = $this->request->inputs(['market_id','pay_coin','code']);

        $this->validated($data, \App\Validation\Api\OtcPopValidation::class);
        //验证短信
        $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code']);
        // 发布
        $res = $this->app(OtcOrderService::class)->poplish($this->request->query('userId'),$data);
        if(!$res){
            return $this->fail('下单失败');
        }
        return $this->success('下单成功',$res);
    }

    /*我的发布*/
    public function publist(){

        $where = $this->request->inputs(['otc_coin_id','side']);
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $where['status'] = [1,2];
        $where['user_id'] = $this->request->query('userId');
        $list = $this->app(OtcMarketService::class)->searchApi($where,$page, $perPage);
        return $this->success('我的发布',$list);

    }
    /*发布详情*/
    public function pubinfo(){
        $userId = $this->request->query('userId');
        $marketId    = $this->request->input('market_id');
        $info = $this->app(OtcMarketService::class)->getQuery()->where(['id'=>$marketId])->with(['user:id,username'])->first();
        return $this->success('发布详情',$info);
    }
    /*发布启用*/
    public function enable(){
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_enable')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $userId = $this->request->query('userId');
        $market_id = $this->request->input('market_id');
        $result = $this->app(OtcMarketService::class)->enable($userId,$market_id);
        if(!$result){
            return $this->fail('启用失败');
        }
        return $this->success('启用成功');
    }
    /*发布禁用*/
    public function disabe(){
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_disabe')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $userId = $this->request->query('userId');
        $market_id = $this->request->input('market_id');
        $result = $this->app(OtcMarketService::class)->disabe($userId,$market_id);
        if(!$result){
            return $this->fail('禁用失败');
        }
        return $this->success('禁用成功');
    }
    /*发布撤销*/
    public function remove(){
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_remove')){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $userId = $this->request->query('userId');
        $market_id = $this->request->input('market_id');
        $result = $this->app(OtcMarketService::class)->remove($userId,$market_id);
        if(!$result){
            return $this->fail('撤销失败');
        }
        return $this->success('撤销成功');
    }
    /*匹配记录*/
    public function poplist(){
        $where = $this->request->inputs(['otc_coin_id']);
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $where['user_id'] = $this->request->query('userId');
        $list = $this->app(OtcOrderService::class)->searchApi($where,$page, $perPage);
        return $this->success('我的发布',$list);
    }

    /*订单详情*/
    public function popinfo()
    {
        $userId = $this->request->query('userId');
        $orderId    = $this->request->input('order_id');
        $info = $this->app(OtcOrderService::class)->getQuery()->where(['id'=>$orderId])->where( function($query) use ($userId){
             $query->where('users_uid', $userId)->orWhere('other_uid',$userId);
        })->with(['user:id,username','target:id,username'])->first();
        return $this->success('匹配详情',$info);
    }

    /*市场统计*/
    public function counts()
    {
        $coin_id    = $this->request->input('otc_coin_id');
        $hours_24_number =  $this->app(OtcOrderService::class)->getQuery()->where('otc_coin_id',$coin_id)->whereTime('deal_time','-24 hours')->sum('number');
        $hours_24_amount =  $this->app(OtcOrderService::class)->getQuery()->where('otc_coin_id',$coin_id)->whereTime('deal_time','-24 hours')->sum('total_price');
        $hours_24_number =  $this->cus_floatval($hours_24_number,6);
        $hours_24_amount =  $this->cus_floatval($hours_24_amount,6);
        $hours_24_price  =  $this->app(OtcCoinsService::class)->getQuery()->where('id',$coin_id)->value('limit_min_price');
        $hours_24_owner  =  $this->app(UserBalanceService::class)->getQuery()->where('red','>',0)->sum('red');
        return $this->success('统计信息',compact('hours_24_number','hours_24_amount','hours_24_price','hours_24_owner'));
    }

}
