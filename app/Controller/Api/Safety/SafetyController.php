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
namespace App\Controller\Api\Safety;

use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserRewardService;
use App\Common\Service\Users\UserSafetyCouponsService;
use App\Common\Service\Users\UserWithdrawService;
use Carbon\Carbon;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserSafetyService;
use App\Common\Service\Users\UserSafetyOrderService;
use App\Common\Service\System\SysSafetyService;
use App\Common\Service\System\SysConfigService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Traits\HelpTrait;


class SafetyController extends BaseController
{
    use HelpTrait;

    /**
     * @var UserSafetyService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, UserSafetyService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists(){
        $result = $this->app(SysSafetyService::class)->searchApi();
        return $this->success('success',$result);
    }

    public function info(){
        $result = $this->app(SysSafetyService::class)->searchApi($this->request->input('id'));
        if(!$result){ return $this->fail('fail');}
        return $this->success('success',$result);
    }

    /**
     * 购买保险
     */
    public function create()
    {
        $userId = $this->request->query('userId');
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_safety_create_' . $userId,2)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['safety_id','paysword','method']);
        if(!isset($data['method']) || empty($data['method'])){
            return $this->fail('method_error'); //付款方式错误
        }
        //验证密码
        $this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        // 添加
        if($data['method'] == "coupons"){
            $res = $this->service->coupons($this->request->query('userId'),$data['safety_id']);
        }else{
            $res = $this->service->create($this->request->query('userId'),$data['safety_id']);
        }
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }

    /**
     * 保险卷记录
     */
    public function coupons()
    {

        $where['user_id'] = $this->request->query('userId');

        $where['statusIn'] = $this->request->input('status') > 0 ? [1,2] :[0] ;

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserSafetyCouponsService::class)->search($where,['user:id,username,email,mobile,is_bind','target:id,username,email,mobile,is_bind'],$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 赠送用户保险卷
     */
    public function sendCoupons()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_sendCoupons',5)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['id','target']);
        $target =  $this->app(UserService::class)->getQuery()->where('email',trim($data['target']) )->orWhere('mobile',trim($data['target']) )->first();
        if(!$target ){
            return $this->fail('target_error');//对方不存在
        }
        $userId =$this->request->query('userId');
        if($target->id == $userId ){
            return $this->fail('target_error');//对方不存在
        }
        $coupons = $this->app(UserSafetyCouponsService::class)->getQuery()->where('status',0)->where('user_id',$userId)->where('id', $data['id'])->first();
        if(!$coupons){
            return $this->fail('couposn_not');//保险卷不存在
        }
        $res = $this->app(UserSafetyCouponsService::class)->found($target->id,$coupons->user_id,$coupons->order_sn);
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }

    /**
     * 赠送用户保险卷-多张
     */
    public function sendBatch()
    {
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_sendCoupons',5)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['number','target']);
        $target =  $this->app(UserService::class)->getQuery()->where('email',trim($data['target']) )->orWhere('mobile',trim($data['target']) )->first();
        if(!$target){
            return $this->fail('target_error');//对方不存在
        }
        $userId =$this->request->query('userId');
        if($target->id == $userId ){
            return $this->fail('target_error');//对方不存在
        }
        $couponsList = $this->app(UserSafetyCouponsService::class)->getQuery()->where('status',0)->where('user_id',$userId)->get()->toArray();
        if ( 0>=$data['number'] ||  abs($data['number']) > count($couponsList)) {
            return $this->fail('conpons_balance');//保险卷不足
        }
       foreach ($couponsList as $key=>$coupons){
           if( ($key + 1) > abs($data['number'])){
               break;
           }
           $this->app(UserSafetyCouponsService::class)->found($target->id,$coupons['user_id'],$coupons['order_sn']);
       }
        return $this->success('success');
    }


    /*购买记录*/
    public function logs()
    {
        $where['user_id'] = $this->request->query('userId');

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,['safety:id,title'],$page,$perPage);

        return $this->success('success',$lists);
    }


    /*赔付记录*/
    public function order()
    {
        $where['user_id'] = $this->request->query('userId');

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserSafetyOrderService::class)->search($where,['safety:id,title'],$page,$perPage);

        return $this->success('success',$lists);
    }

    /*充值赠送卷统计*/
    public function reward()
    {
        $user_id = $this->request->query('userId');
        $lastMonthSameDay  = Carbon::now()->subMonth()->day(20)->format('Y-m-d 00:00:00');//上月20号
        $nowMonthSameDay  = Carbon::now()->day(21)->format('Y-m-d 00:00:00');//本月20号
        list($child_yeji,$safety_real) = $this->app(UserRewardService::class)->safetyCount($user_id,$lastMonthSameDay,$nowMonthSameDay);
        $safety_real = intval($safety_real);
        return $this->success('success',compact('child_yeji','safety_real'));
    }


}
