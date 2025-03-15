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
namespace App\Controller\Sys\Users;

use App\Common\Model\Users\UserExtend;
use App\Common\Service\Rabc\UsersService;
use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\SecondKline;
use App\Common\Service\Subscribe\SecondKlineData;
use App\Common\Service\System\SysSecondKlineService;
use App\Common\Service\System\SysSportMatchService;
use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserCardsService;
use App\Common\Service\Users\UserFoundService;
use App\Common\Service\Users\UserLeaderService;
use App\Common\Service\Users\UserPowerLogService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserPoolsIncomeService;
use App\Common\Service\Users\UserPowerOrderService;
use App\Common\Service\Users\UserRadotService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserRewardService;
use App\Common\Service\Users\UserSafetyOrderService;
use App\Common\Service\Users\UserSecondQuickenService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserWithdrawService;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use PragmaRX\Google2FA\Google2FA;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysContractService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\EmsService;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;
use Upp\Service\BitcoinService;

class UserController extends BaseController
{
    use RedisTrait;
    use HelpTrait;
    /**
     * @var UserService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function demo(){

        try {

            $result =  [];
            $now = Carbon::now();
            $result['start'] = $now->startOfWeek()->timestamp;
            $result['ends'] = $now->endOfWeek()->timestamp;
            $result['week'] =  $this->app(UserSecondService::class)->incomeSettleExtend(520,2,$result['start'] ,$result['ends']);

            $nows = Carbon::now();
            $result['start_'] = $nows->startOfMonth()->timestamp;
            $result['ends_'] = $nows->endOfMonth()->timestamp;
            $result['month'] =  $this->app(UserSecondService::class)->incomeSettleExtend(520,3,$result['start_'],$result['ends_']);

            //$result['total'] = $this->app(UserSecondService::class)->groupsCompute(379,true);
            //$this->app(UserLeaderService::class)->create(1);

            return $this->success('请求成功',$result);

        } catch(\Throwable $e){

            return $this->fail('请求失败',$e->getMessage());

        }

    }

    public function lists()
    {
        $where= $this->request->inputs(['id','parent','account','username','level','duidou','recharge','balance','types','login_ip','remark']);

        try {

            $where['pid'] =  $where['parent'] ? $this->app(UserService::class)->findByOrWhere($where['parent'])->first()->id : "";

            $where['top'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";

        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }


        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $list  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$list);

    }
    /*IP排行*/
    public function link()
    {
        $ip = $this->request->input('ip','');

        $perPage = $this->request->input('limit',10);

        $page = $this->request->input('page',1);

        $offset = ($page - 1) * $perPage ;

        $ip2region = $this->app(\Ip2Region::class);  //new Ip2Region();

        $data = $this->service->getQuery()->where('enable',1)->where('login_ip','<>','')->where('login_ip','like', '%'. trim($ip).'%')->select(Db::raw('count(id) as user_count,login_ip'))->distinct('login_ip')
            ->groupBy('login_ip')->orderBy(Db::raw('count(id)'),'desc')->offset($offset)->limit($perPage)->get();
        foreach ($data as $key=>$value){
            if(!empty($value['login_ip']) ){
                $info = $ip2region->btreeSearch($value['login_ip']);
                list($country, $c, $province, $city) = explode('|', $info['region']);
                $data[$key]['city'] = "{$country}-{$province}-{$city}";
            }else{
                $data[$key]['city'] = "";
            }
        }
        $list['data'] = $data;
        $list['page'] = $page;
        $list['perPage'] = $perPage;
        $list['total'] = $this->service->getQuery()->where('enable',1)->where('login_ip','<>','')->distinct('login_ip')->count('login_ip');
        return $this->success('请求成功',$list);

    }

    /**
     * 批量禁用
     */
    public function sets()
    {
        $ip = $this->request->input('ip');
        if(!$ip){
            return $this->fail('参数失败');
        }
        $type = $this->request->input('type','');
        if($type=='user'){//伞下批量禁用
            $pid =  $this->service->findWhere('username',$ip);
            if(!$pid){return $this->fail('用户名不存在');}
            $userIds =  $this->app(UserRelationService::class)->getTeams($pid->id);
            $userIds[] = $pid->id;
            if(0 >=count($userIds)){return $this->fail('无伞下');}
            $res = $this->service->getQuery()->whereIn('id',$userIds)->update(['enable'=>$this->request->input('status',1)]);
        }else{//ip批量禁用
            $res = $this->service->getQuery()->where('login_ip',$ip)->where('enable',1)->update(['enable'=>0]);
        }
        if(!$res){
            return $this->fail('更新失败');
        }
        return $this->success('更新成功');
    }

    public function check()
    {
        $this->service->checkEmail($this->request->input('value'),$this->request->input('id',0));
        return $this->success('检测成功');
    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()
    {
        $data = $this->request->all();
        $this->validated($data, \App\Validation\Admin\UserCreateValidation::class);
        $data['method'] = 'email';
        // 添加
        $res = $this->service->create($data);
        if($res === false){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功',$res);
    }


    /**
     * 权限管理|用户管理@添加用户
     */
    public function update($id)
    {
        $data = $this->request->inputs(['account','password','paysword','types','remark','code']);
        if(empty($data['password'])){unset($data['password']);}
        if(empty($data['paysword'])){ unset($data['paysword']);}
        $this->validated($data, \App\Validation\Admin\UserUpdateValidation::class);
        // 更新
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('更新失败');
        }
        //更新级别
        if($this->request->input('types') == 1 ){
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['level' => $this->request->input('level',0),'is_sys_level'=>1]);
        }
        //更新渠道缓存
        $this->service->cachePutQudao();
        $this->service->cachePutUserWhite();
        return $this->success('更新成功');
    }


    /**
     * 添加领导
     */
    public function createLead()
    {
        $username = $this->request->input('username');
        $nickname = $this->request->input('nickname','');
        if(!$username){   return $this->fail('ID不存在');}
        $user =  $this->service->findWhere('username',$username);
        if(!$user){
            return $this->fail('用户不存在');
        }
        $count =  Db::table('sys_data_lead')->where('user_id',$user->id)->count();
        if($count){
            return $this->fail('用户已存在');
        }
        $res = Db::table('sys_data_lead')->insert(['username'=>$user->username,'nickname'=>$nickname,'user_id'=>$user->id]);
        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功');
    }

    /*激活钱包获取助记词*/
    public function wallet($id)
    {
        return $this->success('更新成功');
    }

    /*获取助记词*/
    public function export($id)
    {
        $user = $this->service->find($id);
        if(!$user){
            return $this->fail('操作失败');
        }
        // 获取
        $res = $this->service->get_address($user->id,$user->username);
        return $this->success('获取成功',$res);
    }

    /**
     * 权限管理|用户管理@禁用启用
     */
    public function status($id)
    {
        $res = $this->service->updateField($id,'enable',$this->request->input('status'));
        if(!$res){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 出金权限-个人
     */
    public function withdraw($id)
    {
        $res = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_withdraw'=>$this->request->input('status')]);
        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }
    
    /**
     * 出金权限-伞下
     */
    public function withdrow()
    {

        $username = $this->request->input('username');
        if(!$username){
            return $this->fail('参数失败');
        }
        $pid =  $this->service->findWhere('username',$username);
        if(!$pid){return $this->fail('用户名不存在');}
        $userIds =  $this->app(UserRelationService::class)->getTeams($pid->id);
        if(0 >=count($userIds)){return $this->fail('无伞下');}
        $res = $this->app(UserExtendService::class)->getQuery()->whereIn('user_id',$userIds)->update(['is_withdraw'=>$this->request->input('status')]);
        if(!$res){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 自动出金
     */
    public function autodraw($id)
    {
        $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_autodraw'=>$this->request->input('status')]);
        if(!$rel){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 复投权限
     */
    public function reward($id)
    {
        $userIds =  $this->app(UserRelationService::class)->getTeams($id);
        $userIds[] = $id;
        $res = $this->app(UserExtendService::class)->getQuery()->whereIn('user_id',$userIds)->update(['is_reward'=>$this->request->input('status')]);
        if(!$res){
            return $this->fail('操作失败');
        }
        if($this->request->input('status')){
            $this->app(UserRobotService::class)->getQuery()->whereIn('user_id',$userIds)->whereIn('status',[1,2])->update(['is_auto'=>1]);
        }
        return $this->success('操作成功');

    }

    /**
     * 永久等级
     */
    public function raward($id)
    {
        $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_level'=>$this->request->input('status')]);
        if(!$rel){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 恶意用户
     */
    public function malice($id)
    {
        $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_malice'=>$this->request->input('status')]);
        if(!$rel){
            return $this->fail('操作失败');
        }
        if($this->request->input('status')){
            $this->getCache()->set('is_malice_' . $id ,$id);
        }else{
            $this->getCache()->delete('is_malice_' . $id);
        }
        //批量更新恶意用户
        $ids = $this->app(UserExtendService::class)->getQuery()->where('is_malice',1)->pluck('user_id')->toArray();
        $this->getCache()->set('is_malice' , implode(',', $ids));
        return $this->success('操作成功');
    }

    /**
     * 是否对都
     */
    public function duidou($id)
    {
        $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_duidou'=>$this->request->input('status')]);
        if(!$rel){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 是否对都-扶持
     */
    public function duidouExtend($id)
    {
        if(!$this->app(UserExtendService::class)->findByUid($id)['is_duidou']){
            return $this->fail('请先设为对都');
        }

        $rel = $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->update(['is_duidou_extend'=>$this->request->input('status')]);
        if(!$rel){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }


    /**
     * 清楚锁定
     */
    public function clearBind($id)
    {
        $userInfo = $this->service->getQuery()->where('id',$id)->first();

        $this->getCache()->delete("user_word_error_num_{$userInfo->id}");

        return $this->success('操作成功');
    }

    /**
     * 清楚谷歌
     */
    public function clearGoogle($id)
    {
        $userInfo = $this->service->getQuery()->where('id',$id)->first();
        if($userInfo->is_goole){
            $this->service->goole($userInfo);
        }
        return $this->success('操作成功');
    }



    /**
     * 删除用户
     */
    public function remove($id){

        $this->service->remove($id);

        return $this->success('操作成功');
    }

    /**
     * 批量删除用户
     */
    public function batch(){

        $this->service->batch($this->request->input('ids'));

        return $this->success('操作成功');
    }

    /**
     * 移动关系
     */
    public function moveRelation(){

        $uid = $this->request->input('user_id');
        $pid = $this->request->input('parent_id');
        if(empty($uid) || empty($uid) || 0>=intval($pid) || 0>=intval($pid) ){
            return $this->fail('参数不能为空');
        }
        if(intval($uid) == intval($pid) ){
            return $this->fail('关系不能一样');
        }
        $code = $this->request->input('code');
        if (empty($code) || $code != 909090){
            return $this->fail('验证码不能为空或错误');
        }
        $this->app(UserService::class)->moveRelation($uid,$pid);
        $cache = $this->getRedis()->keys('miaoheYue:user_parent_*');
        if($cache){
            call_user_func_array([$this->getRedis(),'del'],$cache);
        }

        return $this->success('操作成功');
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
                $data[$key]['liushui'] = bcadd((string)$count->money,(string)$count->total,6);
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
        $list['KOL'] = $this->app(UserSecondService::class)->groupsCompute($this->request->input('pid'),true);
        return $this->success('请求成功',$list);

    }

    /**获取伞下*/
    public function sorts()
    {
        $where= $this->request->inputs(['uname']);
        try {
            $pid=  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
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

    /**用户报表*/
    public function union()
    {
        $where= $this->request->inputs(['uname','parent','account']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";

            $where['pid'] =  $where['parent'] ? $this->app(UserService::class)->findByOrWhere($where['parent'])->first()->id: "";

            $where['pids'] =  $where['account'] ? $this->app(UserService::class)->findByOrWhere($where['account'])->first()->id : "";
        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        //缓存用户
        $lists = $this->app(UserRelationService::class)->search($where,$page,$perPage);
        $lists->each(function ($value){
            $value['second'] = $this->app(UserSecondService::class)->getQuery()->where('user_id',$value['uid'])->sum('num');
            if($value['counts']['recharge'] > 0 || $value['counts']['withdraw'] > 0){
                if($value['counts']['recharge'] > 0 ){
                    $value['recharge_rate'] = bcdiv($value['counts']['withdraw'],$value['counts']['recharge'],4) * 100;
                }else{
                    $value['recharge_rate'] = 100;
                }
            }else{
                $value['recharge_rate'] = 0;
            }
            if($value['counts']['recharge'] > 0){
                $value['reward_rate'] = bcdiv($value['reward']['income'],$value['counts']['recharge'],4) * 100;
            }else{
                $value['reward_rate'] = 100;
            }
            return $value;
        });

        return $this->success('请求成功',$lists);
    }

    /**渠道报表*/
    public function leader()
    {
        $where= $this->request->inputs(['uname','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        try {
            $where['user_id'] =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";

        } catch (\Throwable $e){
            return $this->fail('参数错误');
        }

        $sort = $this->request->input('sort','user_id');

        $order = $this->request->input('order','asc');

        //缓存用户
        $lists = $this->app(UserLeaderService::class)->search($where,$page,$perPage,$sort,$order);

        return $this->success('请求成功',$lists);
    }

    /**获取统计*/
    public function counts()
    {
        $user_id = $this->request->input('pid');
        $user = $this->service->find($user_id);
        $lists = [];
        $item_0['number'] = $this->cus_floatval(   $this->app(UserBalanceService::class)->findByUid($user_id)['usdt']  );
        $item_0['title'] = "USDT余额";$lists[] = $item_0;

        $item_2['number'] = $this->cus_floatval(   Db::table('user_recharge')->where('recharge_status', 2)->where('order_coin','usdt')
            ->where('user_id',$user_id)->whereIn("order_type",[3,4])->sum('order_mone')  );
        $item_2['title'] = "累计充值USDT";$lists[] = $item_2;
        $item_4['number'] = $this->cus_floatval(   Db::table('user_withdraw')->where('withdraw_status', 2)->where('order_coin','usdt')
            ->where('user_id',$user_id)->whereIn("order_type",[3,4])->sum('order_mone')  );
        $item_4['title'] = "累计提现USDT";$lists[] = $item_4;
        $item_5['number'] = bcsub(strval($item_2['number']),strval( $item_4['number']),2);
        $item_5['title'] = "净入金USDT";$lists[] = $item_5;

        $item_6['number'] = $this->cus_floatval(   Db::table('user_second')->whereDate('created_at',date('Y-m-d'))->where('user_id',$user_id)
            ->where('status', 1)->sum('num')  );
        $item_6['title'] = "今日投注金额";$lists[] = $item_6;

        $item_7['number'] = $this->cus_floatval(   Db::table('user_second')->where('user_id',$user_id)
            ->where('status', 1)->sum('num')  );
        $item_7['title'] = "累计投注总额";$lists[] = $item_7;

        //"今日盈利总额";
        $reward_win = Db::table('user_second_income')->where('reward_time','>=' ,strtotime(date('Y-m-d')))
            ->where('reward_time',"<",strtotime(date('Y-m-d')))->where('user_id',$user_id)
            ->where('reward_type',1)->sum('reward');

        //"今日亏损总额";
        $reward_kui = Db::table('user_second_income')->where('reward_time','>=' ,strtotime(date('Y-m-d')))
            ->where('reward_time',"<",strtotime(date('Y-m-d')))->where('user_id',$user_id)
            ->where('reward_type',2)->sum('reward');

        $item_8['number'] = bcsub(strval( $reward_win),strval($reward_kui),2);
        $item_8['title'] = "今日盈亏";$lists[] = $item_8;

        //"累计盈利总额"// "累计亏损总额";
        $userReward = $this->app(UserRewardService::class)->findByUid($user_id);
        $item_9['number'] = bcsub(strval( $userReward->income),strval($userReward->deficit),2);
        $item_9['title'] = "累计盈亏";$lists[] = $item_9;

        $item_10['number']  = Db::table('user_second_quicken')->where('user_id',$user_id)->whereIn('reward_type',[1])->sum('reward');
        $item_10['title'] = "推广奖总额";$lists[] = $item_10;
        $item_11['number']  = Db::table('user_second_quicken')->where('user_id',$user_id)->whereIn('reward_type',[2])->sum('reward');
        $item_11['title'] = "团队奖总额";$lists[] = $item_11;

        $item_12['number']  = '登录信息：' .''.$user->login_ip.'，地区：'.$user->login_arae .'，时间：'.$user->login_time;
        $item_12['remark']  =  "注册时间：" . $user->created_at .'，邀请码：'.$user->username.'，ip：'.$user->regis_ip;
        $item_12['title'] = "其他信息";$lists[] = $item_12;

        return $this->success('请求成功',$lists);
    }

    /**
     * 团队报表
     */
    public function groups()
    {

        $lists = [];
        $user_id = $this->request->input('pid',0);
        if(0>=$user_id){return $this->success('请求成功');}
        $item_1['number'] = $this->app(UserService::class)->getQuery()->whereIn('id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->count();
        $item_1['title'] = "注册人数";$lists[] = $item_1;

        $item_2['number'] = Db::table('user_count')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('recharge','>',0)->count();
        $item_2['title'] = "充值人数";$lists[] = $item_2;

        $item_3['number'] = $this->cus_floatval(   Db::table('user_count')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('recharge')  );//线上-trc-bsc;
        $item_3['title'] = "累计充值USDT";$lists[] = $item_3;

        $item_4['number'] = $this->cus_floatval(   Db::table('user_count')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('withdraw')  );
        $item_4['title'] = "累计提现USDT";$lists[] = $item_4;

        $item_5['number'] = bcsub(strval($item_3['number']),strval( $item_4['number']),2);
        $item_5['title'] = "净入金USDT";$lists[] = $item_5;


        $user_v1_num = Db::table('user_extend')->where('level', 1)->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->count();
        $user_v2_num = Db::table('user_extend')->where('level', 2)->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->count();
        $user_v3_num = Db::table('user_extend')->where('level', 3)->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->count();
        $item_6['number'] = "V1:{$user_v1_num}, V2:{$user_v2_num}, V3:{$user_v3_num}";
        $item_6['title'] = "伞下级别";$lists[] = $item_6;

        $item_7['number'] = Db::table('user_second')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('status', 1)->distinct('user_id')->count('user_id');
        $item_7['title'] = "伞下投注人数";$lists[] = $item_7;

        $second_total = Db::table('user_second')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('status', 1)->sum('num');
        $item_8['number'] = bcadd(strval($second_total),"0",6) ;
        $item_8['title'] = "伞下投注总额";$lists[] = $item_8;

        $second_income = Db::table('user_reward')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('income','>',0)->sum('income');

        $second_deficit = Db::table('user_reward')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->where('deficit','>',0)->sum('deficit');

        $item_9['number'] = bcsub(strval($second_income),strval($second_deficit),6);
        $item_9['title'] = "伞下盈亏总额";$lists[] = $item_9;

        $robot_dnamic = Db::table('user_second_quicken')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('reward_type',[1])->sum('reward');
        $item_10['number'] = $robot_dnamic;
        $item_10['title'] = "推广奖总额";$lists[] = $item_10;

        $robot_groups = Db::table('user_second_quicken')->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereIn('reward_type',[2])->sum('reward');
        $item_11['number'] = $robot_groups;
        $item_11['title'] = "团队奖总额";$lists[] = $item_11;

        $item_12['number'] =  $this->app(UserBalanceService::class)->getQuery()->whereIn('user_id', function ($query) use($user_id){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user_id);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->sum('usdt');
        $item_12['title'] = "团队USDT余额(测试已除)";$lists[] = $item_12;

        return $this->success('请求成功',$lists);
    }


    /*体验申请记录*/
    public function found()
    {
        $where = $this->request->inputs(['uname']);
        $where['found_id'] =  $where['uname'] ? $this->service->findByOrWhere($where['uname'])->first()->id : "";
        $perPage = $this->request->input('limit',10);
        $page = $this->request->input('page',1);
        $lists = $this->app(UserFoundService::class)->search($where,$page,$perPage);
        return $this->success('请求成功',$lists);

    }

    /**关系图谱*/
    public function relation()
    {
        $where= $this->request->inputs(['uname']);
        $nodes = [];$edges=[];
        $userId =  $where['uname'] ? $this->app(UserService::class)->findByOrWhere($where['uname'])->first()->id : "";
        if (!$userId){return $this->success('请求成功',compact("nodes","edges","userId"));}

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



}
