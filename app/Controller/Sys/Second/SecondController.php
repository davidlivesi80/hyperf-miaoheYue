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
namespace App\Controller\Sys\Second;



use App\Common\Service\System\SysMarketsService;
use App\Common\Service\System\SysSecondKlineService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserRewardService;
use App\Common\Service\Users\UserSecondIncomeService;
use Carbon\Carbon;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserSecondKolService;
use App\Common\Service\Users\UserService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysSecondService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class SecondController extends BaseController
{

    /**
     * @var SysSecondService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, SysSecondService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {

        $where= $this->request->inputs(['market']);

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
        $this->validated($data, \App\Validation\Admin\SecondValidation::class);

        $data['market'] = strtolower($data['symbol']).strtolower($data['currency']);
        $data['symbol'] = strtoupper($data['symbol']);$data['currency'] = strtoupper($data['currency']);
        // 添加
        $res = $this->service->create($data);
        if(!$res){
            return $this->fail('添加失败');
        }
        //更新缓存
        $this->service->cachePutSecond();
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {
        $data = $this->request->post();
        $this->validated($data, \App\Validation\Admin\SecondValidation::class);
        $data['market'] = strtolower($data['symbol']).strtolower($data['currency']);
        $data['symbol'] = strtoupper($data['symbol']);$data['currency'] = strtoupper($data['currency']);
        // 更新
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }
        //更新涨跌
        $count = $this->app(SysMarketsService::class)->getQuery()->where('symbol',strtoupper($data['market']))->count();
        if(!$count){
            $this->app(SysMarketsService::class)->create(['second_id'=>$id,'symbol'=>strtoupper($data['market']),'coin'=>strtoupper($data['symbol'])]);
        }else{
            $this->app(SysMarketsService::class)->getQuery()->where('symbol',strtoupper($data['market']))->update(['second_id'=>$id,'symbol'=>strtoupper($data['market']),'coin'=>strtoupper($data['symbol'])]);
        }
        //更新缓存
        $this->service->cachePutSecond();
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
        $this->service->cachePutSecond();
        return $this->success('操作成功');
    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function tops ($id)
    {
        $res = $this->service->updateField($id,'tops',$this->request->input('status'));
        if(!$res){
            return $this->fail('操作失败');
        }
        //更新缓存
        $this->service->cachePutSecond();
        return $this->success('操作成功');
    }


    public function order()
    {

        $where= $this->request->inputs(['uname','account','settle','profit','market','fee_rate','scene','timeStart','timeEnd','order_type']);

        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";

            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $where['order_type'] = $where['order_type']==1 ? $where['order_type'] : 0;

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $sort = $this->request->input('sort','id');

        $order = $this->request->input('order','desc');

        $where['status'] = 1;

        $lists  = $this->app(UserSecondService::class)->search($where,['user:id,username,email,mobile,is_bind','second:id,market,status','userExtend:user_id,is_duidou'],$page,$perPage,$sort,$order);

        return $this->success('请求成功',$lists);
    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function orderCreate()
    {
        return $this->fail('添加失败');

        $data = $this->request->inputs(['total','username']);

        $this->validated($data, \App\Validation\Admin\RobotOrderValidation::class);
        $user = $this->app(UserService::class)->findWhere('username',$data['username']);
        // 添加
        $adminId = $this->request->input('adminId');
        if(!$adminId){
            return $this->fail('管理ID错误');
        }

        $res = $this->app(UserSecondService::class)->create($user->id,$data['total'],1,2,$adminId);

        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */
    public function orderUpdate($id)
    {
        $type = $this->request->input('type','income');
        if($type ==  "cancel"){
            $res =$this->app(UserSecondService::class)->find($id);
            $res = $this->app(UserSecondService::class)->cancel($res);
        }elseif ($type ==  "income"){
            try {
                $order = $this->app(UserSecondService::class)->find($id);
                $res = $this->app(UserSecondService::class)->income($order);

            } catch(\Throwable $e){
                $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
                $this->logger('[盈利结算]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
                return false;
            }
        }elseif ($type ==  "dnamic"){
            $order = $this->app(UserSecondIncomeService::class)->find($id);
            $dnamicRate = $this->app(SysConfigService::class)->value('dnamic_rate');
            $dnamicRateArr = explode('@',$dnamicRate);
            $res = $this->app(UserSecondService::class)->dnamic($order,$dnamicRateArr);
        }elseif ($type ==  "groups"){
            $userSecondKol = $this->app(UserSecondKolService::class)->find($this->request->input('id'));
            $number = $this->request->input('number');
            $groupRate = $this->app(SysConfigService::class)->value('groups_rate');
            $res = $this->app(UserSecondService::class)->groups($userSecondKol, $groupRate,$number);
        }elseif ($type ==  "groubs"){
            $lists = $this->app(UserExtendService::class)->getQuery()->where('level',3)->get()->toArray();
            $groupRate = $this->app(SysConfigService::class)->value('groups_rate');
            $now = Carbon::now(); $startWeek = $now->startOfWeek()->format('Y-m-d');
            foreach ($lists as $userExtend){
                list($orderTotal,$start,$ends,$detailes) = $this->app(UserSecondService::class)->groupsCompute($userExtend['user_id'],true);
                $data['user_id'] = $userExtend['user_id'];
                $data['total'] = $orderTotal;
                $data['rate'] = $groupRate;
                $data['amount']= bcmul((string)$orderTotal, (string)($groupRate/100),6);;
                $data['detail'] = json_encode($detailes,JSON_UNESCAPED_UNICODE);
                $data['created_at'] = date('Y-m-d H:i:s');
                if($this->app(UserSecondKolService::class)->getQuery()->where('user_id',$userExtend['user_id'])->where('created_at','>=',$startWeek)->count()){
                    continue;
                }
                $res = $this->app(UserSecondKolService::class)->getQuery()->insert($data);
            }

        }elseif ($type ==  "win"){
            $userId = 1;
            $market = 'bnbusdt';
            $direct = mt_rand(1,2);
            $period = 60;
            $num =  mt_rand(5,999);
            $res = $this->app(UserSecondService::class)->found($userId, $market,$direct,$period,$num);

        }elseif ($type ==  "create"){
            $res = $this->app(UserSecondService::class)->create();
        }else{
            return $this->fail('操作错误');
        }
        return $this->success('操作成功',$res);
    }

    /**
     * 订单统计
     */
    public function orderMinute()
    {

        $where= $this->request->inputs(['timeStart','timeEnd']);

        $where['status'] = 1;

        $lists = $this->app(UserSecondService::class)->minute($where);

        return $this->success('请求成功',$lists);

    }

    /**
     * 订单统计
     */
    public function orderCount()
    {

        $where= $this->request->inputs(['uname','account','settle','profit','market','fee_rate','scene','timeStart','timeEnd','order_type']);

        try {
            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";

            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $where['types'] = 3;

        $where['duidou'] = 1;

        $lists = $this->app(UserSecondService::class)->counts($where);

        return $this->success('请求成功',$lists);

    }

    /**
     * 释放记录
     */
    public function income()
    {

        $where = $this->request->inputs(['uname','order_id','timeStart','timeEnd']);

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserSecondIncomeService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 收益统计
     */
    public function reward()
    {

        $where = $this->request->inputs(['uname']);

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $where['types'] = 3;

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $sort = $this->request->input('sort','id');

        $order = $this->request->input('order','desc');

        $lists = $this->app(UserRewardService::class)->search($where,$page,$perPage,$sort,$order);

        return $this->success('请求成功',$lists);

    }

    /**
     * 导出
     */
    public function rewardExports()
    {
        set_time_limit(0);

        ini_set('memory_limit', '1024M');

        $where = $this->request->inputs(['uname']);

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $where['types'] = 3;


        $lists = $this->app(UserRewardService::class)->searchExp($where)->toArray();

        $data = [];

        foreach ($lists as $value) {

            $data[] = [
                'uid'=>$value['user_id'],
                'username'=>   $value['user']['is_bind'] == 3 ? $value['user']['mobile']:$value['user']['email'],
                'reward'=>$value['reward'],
                'lirun_today'=>$value['lirun_today']
            ];
        }


        return $this->success('导出成功',$data);

    }


    /**
     * 收益统计
     */
    public function kline()
    {

        $where = $this->request->inputs(['market']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(SysSecondKlineService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function klineCreate()
    {

        $data = $this->request->inputs(['second_id','direct','frequency','period','trade_time','is_malice']);
        $this->validated($data, \App\Validation\Admin\KlineValidation::class);
        $second = $this->app(SysSecondService::class)->find($data['second_id']);
        if(!$second){
            return $this->fail('交易对错误');
        }
        $data['market'] = $second->market;
        $res = $this->app(SysSecondKlineService::class)->create($data);
        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function klineUpdate($id)
    {
        $secondKline = $this->app(SysSecondKlineService::class)->find($id);
        if(!$secondKline){
            return $this->fail('数据错误');
        }

        $res = $this->app(SysSecondKlineService::class)->bsettle($secondKline->market,1725187444);
        if(!$res){
            return $this->fail('修改失败');
        }
        return $this->success('修改成功',$res);

    }

    /**
     * 删除用户
     */
    public function klineRemove($id){

        $res = $this->app(SysSecondKlineService::class)->remove($id);

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    /**
     * 删除用户
     */
    public function kol(){

        $where= $this->request->inputs(['uname']);

        try {
            $userId =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }
        //本周结算出来的
        $now = Carbon::now(); $startWeek = $now->startOfWeek()->format('Y-m-d');
        $lists = $this->app(UserSecondKolService::class)->getQuery()->when($userId, function ($query) use($userId){
            return $query->where('user_id',$userId);
        })->where('created_at','>=',$startWeek)->with('user:id,username,email,mobile,is_bind')->orderBy('id','asc')->get();

        return $this->success('请求成功',$lists);
    }

}
