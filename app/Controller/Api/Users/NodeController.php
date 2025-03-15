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
namespace App\Controller\Api\Users;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseController;
use App\Common\Service\Users\{UserBalanceLogService,
    UserBalanceService,
    UserCountService,
    UserExtendService,
    UserRechargeService,
    UserSecondIncomeService,
    UserSecondService,
    UserService,
    UserRobotService,
    UserRelationService,
    UserWithdrawService};
use App\Common\Service\System\{SysCoinsService, SysConfigService, SysFilesService};
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\{UploadService,EmsService};

class NodeController extends BaseController
{

    /**
     * @var UserService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    /**
     * 渠道登录
     */
    public function login()
    {
        $data = $this->request->inputs(['method','account','password']);
        //数据验证
        if($data['method'] == "mobile"){
            $account = $data['account'];
            $this->validated($data,\App\Validation\Api\LoginNodeValidation::class);
        }else{
            $account = $data['account'];
            $this->validated($data,\App\Validation\Api\LoginNodeValidation::class);
        }
        $account_ip = intval( $this->app(SysConfigService::class)->value('account_ip'));
        if ($account_ip > 0){
            $login_ip = $this->app(UserService::class)->getQuery()->where('login_ip',$account_ip)->count();
            if($login_ip > $account_ip){
                return $this->fail('ip_fail');
            }
        }
        if($data['method'] == "mobile"){
            $userInfo =  $this->app(UserService::class)->findWhere('mobile',$account);
        }else{
            $userInfo =  $this->app(UserService::class)->findWhere('email',$account);
        }
        if(!$userInfo || $userInfo->types != 2){
            return $this->fail('type_fail');
        }
        $result = $this->app(UserService::class)->doLogin($data['method'],$account,$data['password'],$this->request->query('ip'));
        return $this->success('login_success',$result['token']);
    }

    /**
     * 渠道信息
     */
    public function info()
    {
        $userId = $this->request->query('userId');
        $user = $this->app(UserService::class)->find($userId);
        if($user->types == 2){
            $data['is_node']  = 1;
        }else{
            $data['is_node'] = 0;
        }
        $authorities[]  = [
            "menuId"=> 1,
            "title"=>"Dashboard",
            "path"=> "/dashboard",
            "component"=> "",
            "parentId"=> 0,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-monitor",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];

        $authorities[] =[
            "menuId"=> 3,
            "title"=>"数据统计",
            "path"=> "/dashboard/analysis",
            "component"=> "/dashboard/analysis",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-data-analysis",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];
        $authorities[] =[
            "menuId"=> 4,
            "title"=>"伞下会员",
            "path"=> "/users/user",
            "component"=> "/users/user",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-_school",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];
        $authorities[] =[
            "menuId"=> 5,
            "title"=>"充值明细",
            "path"=> "/finance/recharge",
            "component"=> "/finance/recharge",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-_component",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];
        $authorities[] = [
            "menuId"=> 6,
            "title"=>"提现记录",
            "path"=> "/finance/withdraw",
            "component"=> "/finance/withdraw",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-_palette",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];
        $authorities[] = [
            "menuId"=> 7,
            "title"=>"关系图谱",
            "path"=> "/users/relation",
            "component"=> "/users/relation",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-_condition",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];
        $authorities[] = [
            "menuId"=> 8,
            "title"=>"整线管理",
            "path"=> "/users/message",
            "component"=> "/users/message",
            "parentId"=> 1,
            "menuType"=> 0,
            "openType"=> 0,
            "icon"=> "el-icon-edit",
            "sort"=> 50,
            "hide"=> 0,
            "authority"=> "",
            "uid"=> null
        ];

        $data['authorities'] = $authorities;
        $data["roles"] = [];
        return $this->success(__('messages.success') ,$data);
    }


    /*会员统计*/
    public function countsUser()
    {
        $topId = $this->request->query('userId');
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $dateUser = $this->request->input('dateUser','');
        if($dateUser){
            $parent = Db::table('user')->where('username',$dateUser)->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($parent->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
        }else{
            $parent = $this->app(UserService::class)->find($this->request->query('userId'));
        }
        //伞下会员
        $total_number = $this->app(UserService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            $query->where('created_at',">=",$startTime)->where('created_at',"<",$endTime);
        })->count();

        //提醒人数
        $xwithdraw_number = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
            ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('updated_at','>=' ,$startTime)->where('updated_at',"<",$endTime);
            })->when($parent, function ($query) use($parent){
                return $query->whereIn('user_id', function ($query) use($parent){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
                });
            })->distinct('user_id')->count('user_id');

        //充值人数
        $recharge_number = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
            ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('updated_at','>=' ,$startTime)->where('updated_at',"<",$endTime);
            })->when($parent, function ($query) use($parent){
                return $query->whereIn('user_id', function ($query) use($parent){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
                });
            })->whereIn("order_type",[3,4])->distinct('user_id')->count('user_id');

        return $this->success('请求成功',compact('total_number','xwithdraw_number','recharge_number'));
    }

    /*出入金统计*/
    public function countsWithdraw()
    {
        $topId = $this->request->query('userId');
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $dateUser = $this->request->input('dateUser','');
        if($dateUser){
            $parent = Db::table('user')->where('username',$dateUser)->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($parent->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
        }else{
            $parent = $this->app(UserService::class)->find($this->request->query('userId'));
        }

        $data['user_usdt']  = $this->app(UserBalanceService::class)->getQuery()->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->sum('usdt');

        // 已通过
        $data['recharge_usdt'] = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
            ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('updated_at','>=' ,$startTime)->where('updated_at',"<",$endTime);
            })->when($parent, function ($query) use($parent){
                return $query->whereIn('user_id', function ($query) use($parent){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
                });
            })->whereIn("order_type",[3,4])->whereNotIn('user_id',[818,831])->sum('order_mone');//线上

        $data['withdraw_usdt'] = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
            ->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
                return $query->where('updated_at','>=' ,$startTime)->where('updated_at',"<",$endTime);
            })->when($parent, function ($query) use($parent){
                return $query->whereIn('user_id', function ($query) use($parent){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
                });
            })->whereNotIn('user_id',[818,831])->sum('order_mone');//自动

        $data['recharge_deposit'] = bcsub(strval($data['recharge_usdt']),strval($data['withdraw_usdt']) ,6);

        return $this->success('请求成功', $data);
    }

    /*收益统计*/
    public function countsReward()
    {
        $topId = $this->request->query('userId');
        $startTime = $this->request->input('timeStart','');
        $endTime = $this->request->input('timeEnd','');
        $dateUser = $this->request->input('dateUser','');
        if($dateUser){
            $parent = Db::table('user')->where('username',$dateUser)->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($parent->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
        }else{
            $parent = $this->app(UserService::class)->find($topId);
        }
        //伞下总流水
        $orderMone = Db::table('user_second_income')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->where('groups_time',0)->sum('total');

        //流水动态
        $robot_pnamic = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->where('reward_type',1)->where('settle_time',0)->sum('reward');

        //流水动态
        $robot_dnamic = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->whereIn('user_id', function ($query) use($parent){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$parent->id);
            });
        })->where('reward_type',1)->where('settle_time','>',0)->sum('reward');

        //流水团队-本周结算上周的
        $now = Carbon::now();
        $startweek = $now->startOfWeek()->timestamp; $endsweek = $now->endOfWeek()->timestamp;
        $robot_weeks = Db::table('user_second_quicken')->when($parent, function ($query) use($parent){
            return $query->where('user_id',$parent->id);
        })->where('reward_time','>=' ,$startweek)->where('reward_time',"<",$endsweek)->where('reward_type',2)->sum('reward');

        //流水团队-已结算
        $robot_groups = Db::table('user_second_quicken')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('reward_time','>=' ,strtotime($startTime))->where('reward_time',"<",strtotime($endTime));
        })->when($parent, function ($query) use($parent){
            return $query->where('user_id',$parent->id);
        })->where('reward_type',2)->sum('reward');

        return $this->success('请求成功',compact('robot_pnamic','robot_dnamic','robot_groups','robot_weeks','orderMone'));
    }


    /**
     * 渠道流水记录
     */
    public function logs(){
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('username','');
        if($dateUser){
            $parent = Db::table('user')->where('username',$dateUser)->where('user.username', 'like', '%'. trim($dateUser).'%' )
                ->orWhere('user.email','like', '%'. trim($dateUser).'%' )->orWhere('user.mobile','like', '%'. trim($dateUser).'%' )->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($parent->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
        }else{
            $parent = $this->app(UserService::class)->find($this->request->query('userId'));
        }
        $where['top'] = $parent->id;
        $where['timeStart'] = $this->request->input('timeStart','');
        $where['timeEnd'] = $this->request->input('timeEnd','');
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists  = $this->app(UserSecondIncomeService::class)->search($where,$page,$perPage);
        return $this->success('请求成功',$lists);
    }

    /**
     * 伞下会员
     */
    public function users(){
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('username','');
        $dateParent= $this->request->input('parent','');
        if($dateUser){
            $user = $this->app(UserService::class)->findByOrWhere($dateUser)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($user->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
            $where['id'] = $user->id;
        }
        if($dateParent){
            $parent = $this->app(UserService::class)->findByOrWhere($dateParent)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($parent->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
            $where['pid'] = $parent->id;
        }
        $where['top'] = $topId;
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists  = $this->app(UserService::class)->search($where,$page,$perPage);
        return $this->success('请求成功',$lists);
    }

    public function child()
    {
        //缓存用户
        $data = $this->app(UserRelationService::class)->getQuery()->where('pid',$this->request->input('pid'))->with('user:id,email,mobile,is_bind,username')->get()->toArray();
        $childsIds = [];
        foreach ($data as $key => $value){
            $childsIds[] = $value['uid'];
            $count = $this->app(UserCountService::class)->findByUid($value['uid']);
            $extend = $this->app(UserExtendService::class)->findByUid($value['uid']);
            if($value['user']){
                $username = $value['user']['is_bind'] == 3 ? $value['user']['mobile'] : $value['user']['email'] ;
                $data[$key]['user']['username'] = $username;
            }
            if($count){
                $data[$key]['self'] = $count->self;
                $data[$key]['team'] = $count->team;
                $data[$key]['recharge'] =$count->recharge;
                $recharge_sons  =  Db::table('user_count')->whereIn('user_id', function ($query) use($value){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$value['uid']);
                })->sum('recharge');//线上-trc-bsc;
                $data[$key]['recharge_sons'] = $recharge_sons;
                $data[$key]['level'] = $extend->level;
            }else {
                $data[$key]['self'] = 0;
                $data[$key]['team'] = 0;
                $data[$key]['level'] = 0;
            }
        }
        $list['data'] = $data;
        $list['child'] = count($list['data']);
        $list['child_xiao'] = count($this->app(UserRelationService::class)->getChild($this->request->input('pid'),true));
        $list['team']  = count($this->app(UserRelationService::class)->getTeams($this->request->input('pid')));
        $list['counts'] = $userCount = $this->app(UserCountService::class)->findByUid($this->request->input('pid'));
        $relation = $this->app(UserRelationService::class)->getQuery()->whereIn('uid',$this->app(UserRelationService::class)->getParent($this->request->input('pid')))->with('user:id,email,mobile,is_bind,username')->get()->toArray();
        foreach ($relation as  $key=>$pid){
            $count = $this->app(UserCountService::class)->findByUid($pid['uid']);
            $extend = $this->app(UserExtendService::class)->findByUid($pid['uid']);
            if($pid['user']){
                $username = $pid['user']['is_bind'] == 3 ? $pid['user']['mobile'] : $pid['user']['email'] ;
                $relation[$key]['user']['username'] = $username;
            }
            if($count){
                $relation[$key]['self'] =$count->self;
                $relation[$key]['team'] =  $count->team;
                $relation[$key]['recharge'] = $count->recharge;
                $recharge_sons  =  Db::table('user_count')->whereIn('user_id', function ($query) use($pid){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$pid['uid']);
                })->sum('recharge');//线上-trc-bsc;
                $relation[$key]['recharge_sons'] = $recharge_sons;
                $relation[$key]['level'] = $extend->level;
            }else {
                $relation[$key]['self'] = 0;
                $relation[$key]['team'] = 0;
                $relation[$key]['level'] = 0;
            }
        }
        $list['relation'] = $relation;
        $groups_rule = explode('@',$this->app(SysConfigService::class)->value('groups_rule'));
        $groups_nums = explode('@',$this->app(SysConfigService::class)->value('groups_nums'));
        $list['findLevel']  = $this->app(UserCountService::class)->findLevel($userCount,$groups_rule,$groups_nums,true);
        return $this->success('请求成功',$list);

    }

    public function luck()
    {

        $lists  = $this->app(SysCoinsService::class)->column([],'coin_name','coin_symbol');

        return $this->success('请求成功',$lists);

    }

    /**
     * 充值明细
     */
    public function recharge(){
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('uname','');
        if($dateUser){
            $user = $this->app(UserService::class)->findByOrWhere($dateUser)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($user->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
            $where['user_id'] = $user->id;
        }

        $where['top'] = $topId;
        $where['status'] = 2;

        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists = $this->app(UserRechargeService::class)->search($where,$page,$perPage);
        return $this->success('请求成功',$lists);
    }

    /**
     * 提现明细
     */
    public function withdraw(){
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('uname','');
        if($dateUser){
            $user = $this->app(UserService::class)->findByOrWhere($dateUser)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($user->id);
            if(!in_array($topId,$parentIds)){
                return $this->fail('不是伞下');
            }
            $where['user_id'] = $user->id;
        }

        $where['top'] = $topId;
        $where['status'] = 2;

        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists = $this->app(UserWithdrawService::class)->search($where,$page,$perPage);
        return $this->success('请求成功',$lists);
    }

    /**关系图谱*/
    public function relation()
    {
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('uname','');
        $nodes = [];$edges=[];
        if($dateUser){
            $user = $this->app(UserService::class)->findByOrWhere($dateUser)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($user->id);
            if(!in_array($topId,$parentIds) && $topId != $user->id){
                return $this->fail('不是伞下');
            }
            $userId = $user->id;
            if (!$userId){return $this->success('请求成功',compact("nodes","edges"));}
        }else{
            return $this->success('请求成功',compact("nodes","edges"));
        }

        //$nodes格式：['id'=>2,"label"=>'box-2',"shape"=>"box"]  $edges格式：['from'=>1,"to"=>2]
        $childs_level_1 = $this->app(UserRelationService::class)->getQuery()->where('pid',$userId)->orWhere('uid',$userId)->with(['user:id,username,email,mobile,is_bind'])->get();
        $childs_level_1_pids = [];
        foreach ($childs_level_1 as $key => $child){
            $recharge = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
                ->whereIn('user_id', function ($query) use($child){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
                })->whereIn("order_type",[3,4])->sum('order_mone');//线上
            $withdraw = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
                ->whereIn('user_id', function ($query) use($child){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
                })->sum('order_mone');
            $label = $child['user']['is_bind'] == 3 ? $child['user']['mobile']: $child['user']['email'];
            $label = "【" . $child->uid ."】" . $label;
            $label = $label . "|" ."入金:". $this->cus_floatval($recharge);
            $label = $label . "|" ."出金:". $this->cus_floatval($withdraw);
            $label = $label . "|" ."剩余:".  bcsub((string)$recharge,(string)$withdraw,2);
            $nodes[] = ['id'=>$child->uid,"label"=>$label];
            $edges[] = ['from'=>$child->pid,"to"=>$child->uid];
            if($child->uid != $userId){
                $childs_level_1_pids[] = $child->uid;
            }
        }

        $childs_level_2 = $this->app(UserRelationService::class)->getQuery()->whereIn('pid',$childs_level_1_pids)->with(['user:id,username,email,mobile,is_bind'])->get();
        $childs_level_2_pids = [];
        foreach ($childs_level_2 as $key => $child){
            $recharge = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
                ->whereIn('user_id', function ($query) use($child){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
                })->whereIn("order_type",[3,4])->sum('order_mone');//线上
            $withdraw = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
                ->whereIn('user_id', function ($query) use($child){
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
                })->sum('order_mone');
            $label = $child['user']['is_bind'] == 3 ? $child['user']['mobile']: $child['user']['email'];
            $label = "【" . $child->uid ."】" . $label;
            $label = $label . "|" ."入金:". $this->cus_floatval($recharge);
            $label = $label . "|" ."出金:". $this->cus_floatval($withdraw);
            $label = $label . "|" ."剩余:".  bcsub((string)$recharge,(string)$withdraw,2);
            $nodes[] = ['id'=>$child->uid,"label"=>$label];
            $edges[] = ['from'=>$child->pid,"to"=>$child->uid];
            $childs_level_2_pids[] = $child->uid;

        }

        // $childs_level_3 = $this->app(UserRelationService::class)->getQuery()->whereIn('pid',$childs_level_2_pids)->with(['user:id,username,email,mobile,is_bind'])->get();
        // $childs_level_3_pids = [];
        // foreach ($childs_level_3 as $key => $child){
        //     $recharge = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        //         ->whereIn('user_id', function ($query) use($child){
        //             return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
        //         })->whereIn("order_type",[3,4])->sum('order_mone');//线上
        //     $withdraw = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
        //         ->whereIn('user_id', function ($query) use($child){
        //             return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
        //         })->sum('order_mone');
        //     $label = $child['user']['is_bind'] == 3 ? $child['user']['mobile']: $child['user']['email'];
        //     $label = "【" . $child->uid ."】" . $label;
        //     $label = $label . "|" ."入金:". $this->cus_floatval($recharge);
        //     $label = $label . "|" ."出金:". $this->cus_floatval($withdraw);
        //     $label = $label . "|" ."剩余:".  bcsub((string)$recharge,(string)$withdraw,2);
        //     $nodes[] = ['id'=>$child->uid,"label"=>$label];
        //     $edges[] = ['from'=>$child->pid,"to"=>$child->uid];
        //     $childs_level_3_pids[] = $child->uid;

        // }

        // $childs_level_4 = $this->app(UserRelationService::class)->getQuery()->whereIn('pid',$childs_level_3_pids)->with(['user:id,username,email,mobile,is_bind'])->get();
        // $childs_level_4_pids = [];
        // foreach ($childs_level_4 as $key => $child){
        //     $recharge = Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
        //         ->whereIn('user_id', function ($query) use($child){
        //             return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
        //         })->whereIn("order_type",[3,4])->sum('order_mone');//线上
        //     $withdraw = Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
        //         ->whereIn('user_id', function ($query) use($child){
        //             return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$child->uid);
        //         })->sum('order_mone');
        //     $label = $child['user']['is_bind'] == 3 ? $child['user']['mobile']: $child['user']['email'];
        //     $label = "【" . $child->uid ."】" . $label;
        //     $label = $label . "|" ."入金:". $this->cus_floatval($recharge);
        //     $label = $label . "|" ."出金:". $this->cus_floatval($withdraw);
        //     $label = $label . "|" ."剩余:".  bcsub((string)$recharge,(string)$withdraw,2);
        //     $nodes[] = ['id'=>$child->uid,"label"=>$label];
        //     $edges[] = ['from'=>$child->pid,"to"=>$child->uid];
        //     $childs_level_4_pids[] = $child->uid;

        // }


        return $this->success('请求成功',compact("nodes","edges","userId"));
    }

    /**获取伞下-整线管理*/
    public function sorts()
    {
        $topId = $this->request->query('userId');
        $dateUser = $this->request->input('uname','');
        if($dateUser){
            $user = $this->app(UserService::class)->findByOrWhere($dateUser)->first();
            $parentIds = $this->app(UserRelationService::class)->getParent($user->id);
            if(!in_array($topId,$parentIds) && $topId != $user->id){
                return $this->fail('不是伞下');
            }
            $pid = $user->id;
            if (!$pid){return $this->success('请求成功',[]);}
        }else{
            return $this->success('请求成功',[]);
        }

        //缓存用户
        if($pid){
            $lists = $this->app(UserRelationService::class)->getQuery()->where('pid',$pid)->select(['uid','pid'])->orderBy('uid','desc')->with(['user:id,username,email,mobile,is_bind','extend:user_id,level','counts:user_id,self,team,recharge,recharge_sys,withdraw','balance:user_id,usdt','reward'])->get()->toArray();
            foreach ($lists as $key => $value){
                $lists[$key]['second'] = $this->app(UserSecondService::class)->getQuery()->where('user_id',$value['uid'])->sum('num');
                if($value['counts']['recharge'] > 0 || $value['counts']['withdraw'] > 0){
                    if($value['counts']['recharge'] > 0 ){
                        $lists[$key]['recharge_rate'] = bcdiv($value['counts']['withdraw'],$value['counts']['recharge'],4) * 100;
                    }else{
                        $lists[$key]['recharge_rate'] = 100;
                    }
                }else{
                    $lists[$key]['recharge_rate'] = 0;
                }
                if($value['counts']['recharge'] > 0){
                    $lists[$key]['reward_rate'] = bcdiv($value['reward']['income'],$value['counts']['recharge'],4) * 100;
                }else{
                    $lists[$key]['reward_rate'] = 100;
                }
                $hasChildren = $this->app(UserRelationService::class)->getQuery()->where('pid',$value['uid'])->select(['uid','pid'])->count();
                $lists[$key]['hasChildren'] = $hasChildren > 0 ? true:false;
            }
        }else{
            $lists = [];
        }

        return $this->success('请求成功',$lists);
    }



}
