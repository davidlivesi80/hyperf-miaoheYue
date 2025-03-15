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
namespace App\Controller\Sys\Safety;


use App\Common\Service\Users\UserRewardService;
use App\Common\Service\Users\UserSafetyCouponsService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserSafetyService;
use App\Common\Service\Users\UserSafetyOrderService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysSafetyService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class SafetyController extends BaseController
{

    /**
     * @var SysSafetyService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, SysSafetyService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {

        $where= $this->request->inputs(['title']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    public function types()
    {
        $lists  = $this->service->searchApi([]);

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()
    {
        $data = $this->request->all();
        $this->validated($data, \App\Validation\Admin\SafetyValidation::class);
        // 添加
        $res = $this->service->create($data);
        if(!$res){
            return $this->fail('添加失败');
        }
        //更新缓存
        $this->service->cachePutSafety();
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {
        $data = $this->request->post();
        $this->validated($data, \App\Validation\Admin\SafetyValidation::class);
        // 更新
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }

        //更新缓存
        $this->service->cachePutSafety();
        return $this->success('更新成功');
    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function status ($id)
    {
        $res = $this->service->updateField($id,'status',$this->request->input('status'));
        if(!$res){
            return $this->fail('操作失败');
        }
        //更新缓存
        $this->service->cachePutSafety();
        return $this->success('操作成功');
    }

    /**
     * 购买记录
     */
    public function logs()
    {

        $where= $this->request->inputs(['safety_id','user_id','username','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserSafetyService::class)->search($where,['user:id,username,email,mobile,is_bind','safety:id,title,status'],$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 赔付记录
     */
    public function order()
    {

        $where= $this->request->inputs(['safety_id','user_id','username','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserSafetyOrderService::class)->search($where,['user:id,username,email,mobile,is_bind','safety:id,title,status'],$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 添加保险
     */
    public function orderCreate()
    {

        $data = $this->request->inputs(['safety_id','user_id']);
        if(empty($data['safety_id']) || empty($data['user_id'])){
            return $this->fail('保险或用户ID');
        }
        // 添加
        $res = $this->app(UserSafetyService::class)->found($data['user_id'],$data['safety_id']);
        return $this->success('设置成功',$res);

    }

    /**
     * 取消保险
     */
    public function orderClose($id)
    {

        $order = $this->app(UserSafetyService::class)->find($id);
        if(!$order){
            return $this->fail('保险无效');
        }
        $is_return = $this->request->input('is_return',0);
        // 添加
        $res = $this->app(UserSafetyService::class)->cancel($order,$is_return);
        return $this->success('设置成功',$res);

    }

    /**
     * 开启赔付
     */

    public function orderUpdate($id=0)
    {
        $data = $this->request->inputs(['user_ids','nums_ids']);
        if(empty($data['user_ids']) || empty($data['nums_ids'])){
            return $this->fail('参数不能为空');
        }
        $user_ids = explode('@',$data['user_ids']);
        $nums_ids = explode('@',$data['nums_ids']);
        if(count($user_ids) != count($nums_ids)){
            return $this->fail('参数长度不相符');
        }
        $res = 0;
        for ($i = 0; $i < count($user_ids); $i++) {
             $userExtend = $this->app(UserExtendService::class)->getQuery()->where('user_id',$user_ids[$i])->first();
             if($userExtend){
                 $this->app(UserSafetyOrderService::class)->found($userExtend,$nums_ids[$i],$i);
                 $res= $i + 1;
             }
        }
        return $this->success('设置成功',$res);

    }

    /**
     * 个人赔付
     */

    public function orderSelf()
    {

        $data = $this->request->inputs(['user_id','number']);
        if(empty($data['user_id']) || empty($data['number']) ||  0 >= intval($data['user_id']) ||  0 >= intval($data['number']) ){
            return $this->fail('参数设置错误');
        }
        $userExtend = $this->app(UserExtendService::class)->getQuery()->where('user_id',$data['user_id'])->first();
        if(!$userExtend){
            return $this->fail('参数信息错误');
        }
        $res = $this->app(UserSafetyOrderService::class)->found($userExtend,$data['number'],0);
        if(!$res){
            return $this->fail('赔付失败');
        }
        return $this->success('赔付成功',$res);

    }

    /**
     * 赔付预统计
     */
    public function orderCount()
    {

        $where= $this->request->inputs(['safety_id','user_id','username','timeStart','timeEnd']);

        $lists = $this->app(UserSafetyOrderService::class)->counts($where);

        return $this->success('请求成功',$lists);

    }

    /**
     * 保险卷记录
     */
    public function coupons()
    {

        $where= $this->request->inputs(['status','user_id','username','timeStart','timeEnd']);

        try {
            $where['user_id'] =  $where['username'] ? $this->app(UserService::class)->findByOrWhere($where['username'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误：' . $e->getMessage());
        }

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->app(UserSafetyCouponsService::class)->search($where,['user:id,username,email,mobile,is_bind','target:id,username,email,mobile,is_bind'],$page,$perPage);

        return $this->success('请求成功',$lists);
    }

    /**
     * 系统赠送卷
     */

    public function couponsCreate()
    {

        $data = $this->request->inputs(['user_id','number']);
        if(empty($data['user_id']) || empty($data['number']) || 0 > $data['number']){
            return $this->fail('用户或数量不能空');
        }
        $user =  $this->app(UserService::class)->find($data['user_id']);
        if(!$user){
            return $this->fail('用户不存在');
        }
        for ($i=0;$i<$data['number'];$i++){
            $res = $this->app(UserSafetyCouponsService::class)->create($data['user_id'],0,1);
        }
        if(!$res){
            return $this->fail('设置失败');
        }
        return $this->success('设置成功',$res);

    }

    /**
     * 代发赠送用户
     */

    public function couponsUpdate($id)
    {
        $data = $this->request->inputs(['user_id']);
        if( empty($data['user_id']) ){
            return $this->fail('用户或数量不能空');
        }

        $user =  $this->app(UserService::class)->find($data['user_id']);
        if(!$user){
            return $this->fail('用户不存在');
        }
        $coupons = $this->app(UserSafetyCouponsService::class)->getQuery()->where('status',0)->where('id', $id)->first();
        if(!$coupons){
            return $this->fail('不存在有效保险卷');
        }
        $res = $this->app(UserSafetyCouponsService::class)->found($data['user_id'],$coupons->user_id,$coupons->order_sn);
        if(!$res){
            return $this->fail('设置失败');
        }
        return $this->success('设置成功',$res);
    }


    /**
     * 充值赠送卷统计
     */
    public function reward()
    {

        $where = $this->request->inputs(['uname']);

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserRewardService::class)->searchSafety($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


}
