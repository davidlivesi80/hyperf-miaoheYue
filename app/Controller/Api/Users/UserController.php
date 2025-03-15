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


use Hyperf\DbConnection\Db;
use PragmaRX\Google2FA\Google2FA;
use Psr\SimpleCache\CacheInterface;
use Upp\Basic\BaseController;
use App\Common\Service\Users\{UserBankService,
    UserCardsService,
    UserCountService,
    UserExtendService,
    UserFoundService,
    UserLockedOrderService,
    UserMessageService,
    UserPowerIncomeService,
    UserPowerService,
    UserRadotIncomeService,
    UserRechargeService,
    UserRewardService,
    UserRobotIncomeService,
    UserRobotQuickenService,
    UserSecondIncomeService,
    UserSecondQuickenService,
    UserSecondService,
    UserService,
    UserRelationService,
    UserWithdrawService,
    UserBalanceService};
use App\Common\Service\System\{SysConfigService, SysEmailService, SysFilesService};
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\{ParseToken, SmsService, UploadService, EmsService};
use Carbon\Carbon;


class UserController extends BaseController
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
     * 用户信息
     */
    public function info($with = '')
    {
        $userId = $this->request->query('userId');

        $fields = ['id','username','email','mobile','login_time','types','is_bind','is_goole','is_lock','spread','paysword','google2fa_secret'];

        $user = $this->service->findWith($userId,['extend','counts:user_id,xeam,self','relation:uid,pid'],$fields);

        if($user['relation']['pid'] > 0){
            $parent = $this->service->find($user->relation->pid);
            if($parent){
                $user['parent'] = $parent->username;
                $user['parent_avatar'] = $parent->extend->avatar;
            }else{
                $user['parent'] = '';
                $user['parent_avatar'] = '';
            }
        }else{
            $user['parent'] = '';
        }

        if($user->email){
            $user['email'] = substr_replace($user['email'], '****', 3, 4);
        }
        if($user->mobile){
            $user['mobile'] = substr_replace($user['mobile'], '****', 3, 4);
        }

        if($user->paysword){
            $user['is_paysword'] = 1;
        }else{
            $user['is_paysword'] = 0;
        }

        unset($user['paysword']);

        if($user->is_goole > 0){
           unset($user['google2fa_secret']);
        }
        //更新上级缓存,获取体验账号，仅限打单
        $parentIds = $this->app(UserRelationService::class)->getParent($userId);
        $user['types'] = $user['types'] == 3 ? 3 : 0;
        //更新登录时间，激活活跃
        if($user->login_time){
            if(strtotime(date("Y-m-d")) > strtotime($user->login_time)){
                $this->service->getQuery()->where('id',$user->id)->update(['login_time'=>date('Y-m-d H:i:s')]);
            }
        }else{
            $this->service->getQuery()->where('id',$user->id)->update(['login_time'=>date('Y-m-d H:i:s')]);
        }
        return $this->success(__('messages.success',[],)  ,$user);
    }

    /**
     * 登录密码
     */
    public function password()
    {
        $data = $this->request->inputs(['password','password_confirmation','code','method']);
        //数据验证
        $this->validated($data,\App\Validation\Api\PasswordValidation::class);
        //验证短信,谷歌
        if($data['method']){
            $this->app(UserService::class)->checkGoole($this->request->query('userId'),$data['code']);
        }else{
            $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'forget');
        }
        $this->service->update($this->request->query('userId'),['password'=>$data['password']]);
        return $this->success('edit_success');
    }

    /**
     * 支付密码、谷歌验证器
     */
    public function check()
    {
        $data = $this->request->inputs(['paysword',"password_confirmation"]);
        //数据验证
        $this->validated($data,\App\Validation\Api\GoooleValidation::class);
        //验证密码
        $this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        return $this->success('verify_success',$this->service->checkPaysCache($this->request->query('userId')));
    }

    /**
     * 支付密码
     */
    public function paysword()
    {
        $data = $this->request->inputs(['oldspass','paysword','r_paysword','code','method']);
        //1. 数据验证
        $this->validated($data,\App\Validation\Api\PayswordValidation::class);
        //验证短信,谷歌
        if($data['method']){
            $this->app(UserService::class)->checkGoole($this->request->query('userId'),$data['code']);
        }else{
            $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'forget');
        }

        $this->service->update($this->request->query('userId'),['paysword'=>$data['paysword']]);
        return $this->success('edit_success');
    }

    /**
     * 修改昵称
     */
    public function nickname()
    {
        $data = $this->request->inputs(['nickname']);
        //数据验证
        $this->validated($data,\App\Validation\Api\UserEditValidation::class);
        $nickname =  htmlentities($data['nickname']);
        $this->service->find($this->request->query('userId'))->extend()->update(['nickname'=>$nickname]);
        return $this->success('edit_success');
    }

    /**
     * 修改图像
     */
    public function avatar()
    {
        $data = $this->request->inputs(['avatar']);
        //数据验证
        $this->validated($data,\App\Validation\Api\UserEditValidation::class);
        $avatar=  htmlentities($data['avatar']);
        $this->service->find($this->request->query('userId'))->extend()->update(['avatar'=>$avatar]);
        return $this->success('edit_success');
    }

    /**
     * 更换验证
     */
    public function bindold()
    {
        $data = $this->request->inputs(['code','email']);
        //数据验证
        $this->validated($data,\App\Validation\Api\BindValidation::class);
        //验证短信
        $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'bind');
        return $this->success('verify_success');
    }

    /**
     * 更换邮箱
     */
    public function bindnew()
    {
        return $this->fail('禁止操作');

        $data = $this->request->inputs(['code','email','type']);
        if($data['type'] == 2){
            //数据验证
            $this->validated($data,\App\Validation\Api\CodeValidation::class);
        }else{
            //数据验证
            $this->validated($data,\App\Validation\Api\BindValidation::class);
        }
        $user = $this->service->find($this->request->query('userId'));
        if($user->is_bind){
            return $this->fail('已绑定，请联系客服处理');
        }
        if($data['type'] == 2){
            //验证谷歌
            $valid = (new Google2FA())->verifyKey($user->google2fa_secret, $data['code']);
            if(!$valid){
                return $this->fail('验证码错误');
            }
        }else {
            //验证短信
            $valid = $this->app(EmsService::class)->check('bind',$data['email'],$data['code']);
            if (!$valid) {
                return $this->fail('验证码错误');
            }
        }

        if($data['type'] == 2){
            $data = ['is_bind'=>2];
        }else{
            $data = ['email'=>$data['email'],'is_bind'=>1];
        }

        $this->service->update($user->id,$data);

        return $this->success('修改成功');

    }

    public function upload($id,$filed)
    {

        return $this->fail('upload_fail');

        if ($id) {
            $cateIds = array_column($this->app(SysFilesService::class)->getAllCate(),'id');
            if (!in_array($id,$cateIds)) return $this->fail('目录不存在');
            $cateId = $id;
        } else {
            $cateId = 1;
        }

        $file = $this->request->file($filed);

        $this->validated(['image'=>$file],\App\Validation\Admin\UploadValidation::class);

        $res = $this->app(UploadService::class)->upload($file);
        if(!$res){
            return $this->fail('upload_fail');
        }
        //添加素材数据
        $data = ['cate_id'=>$cateId, 'file_name'=>$res['filename'], 'file_src'=>$res['url'], 'upload_type'=>$res['drive']];

        $this->app(SysFilesService::class)->create(0,0,$data);

        return $this->success('upload_success',$data['file_src']);
    }

    /**
     * 留言反馈
     */
    public function feedback()
    {
        $data = $this->request->inputs(['title','content']);
        $data['title'] = trim($data['title']); $data['content'] = trim($data['content']);
        //数据验证
        $this->validated($data,\App\Validation\Api\FeedbackValidation::class);
        $title =  htmlentities($data['title']);
        $content =  htmlentities($data['content']);
        $this->app(UserMessageService::class)->create($this->request->query('userId'),['title'=>$title,'content'=>$content]);
        return $this->success('edit_success');
    }

    /**
     * 留言记录
     */
    public function feedlist()
    {
        $where['user_id'] = $this->request->query('userId');
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists = $this->app(UserMessageService::class)->searchApi($where,$page,$perPage);
        return $this->success('success',$lists);
    }


    /*提现信息*/
    public function bankInfo()
    {
        $result = $this->app(UserBankService::class)->getQUery()->where(['user_id'=>$this->request->query('userId')])->get();
        return $this->success('success',$result);
    }

    /*绑定提现*/
    public function bank()
    {
        $data = $this->request->inputs(['series','address','paysword',"real",'code']);
        //数据验证
        $this->validated($data,\App\Validation\Api\BankValidation::class);
        //验证短信
        $this->app(UserService::class)->checkCode($this->request->query('userId'),$data['code'],'bind');
        $result = $this->service->bank($this->request->query('userId'),$this->request->query('userName'),$data['series'],$data['address'],$data['real']);
        if(!$result){
            return $this->fail('fail');
        }
        return $this->success('success');
    }

    /*绑定谷歌*/
    public function goole()
    {

        $data = $this->request->inputs(['code',"paysword"]);
        //数据验证
        $user = $this->service->find($this->request->query('userId'));
        if(!$user->is_goole){
            $this->validated($data,\App\Validation\Api\GoooleValidation::class);
            //验证密码
            $this->app(UserService::class)->checkPaysOk($user->id,$data['paysword']);
        }else{
            $this->validated($data,\App\Validation\Api\CodeValidation::class);
            //验证谷歌
            $this->app(UserService::class)->checkGoole($user->id,$data['code']);
        }
        $result = $this->service->goole($user);
        if(!$result){
            return $this->fail('fail');
        }
        return $this->success('success');
    }

    /**
     * 推广信息
     */
    public function spreadInfo()
    {
        $userId = $this->request->query('userId');

        $fields = ['id','username','login_time','created_at'];
        $userinfo = $this->service->findWith($userId,['extend','relation:uid,pid'],$fields);
        $parentInfo = "";
        if($userinfo->relation->pid){
            $parentInfo = $this->service->findWith($userinfo->relation->pid,['extend'],$fields);
        }
        //缓存用户
        $childs = $this->app(UserRelationService::class)->getChild($userId);
        $childIds = array_column($childs,'uid');
        $xiaoIds = $this->app(UserRelationService::class)->getChild($userId,true);
        $teamIds = $this->app(UserRelationService::class)->getTeams($userId);
        $newsIds = $this->service->getQuery()->whereIn('id',function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->whereDate('created_at',date('Y-m-d'))->count();

        //用户信息
        $poster['userinfo'] = $userinfo;
        //上级信息
        $poster['parentInfo'] = $parentInfo;
        //直推人数
        $poster['child_num'] = count($childs);
        //有效直推
        $poster['xiao_num'] = count($xiaoIds);
        //团队人数
        $poster['team_num']  = count($teamIds);
        //今日新增
        $poster['today_num']  = $newsIds;
        //V2直推部门
        $poster['vips_2']  = $this->app(UserCountService::class)->findLevelGroup($childIds);
        return $this->success('success',$poster);

    }

    /**
     * 业绩信息
     */
    public function spreadChild()
    {

        $where['pid'] = $this->request->query('userId');
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $childs = $this->app(UserService::class)->searchApi($where,$page,$perPage);

        return $this->success('success',$childs);
    }

    /**
     * 业绩信息
     */
    public function spreadCount()
    {
        $userId = $this->request->query('userId');
        if($this->request->input('account')){
            $targerId = $this->app(UserService::class)->getQuery()->where('username',$this->request->input('account'))->value('id');
            //判断是不是伞下用户
            $inTeamIds = $this->app(UserRelationService::class)->getQuery()->where('uid',$targerId)->whereRaw('FIND_IN_SET(?,pids)',$userId)->pluck('uid')->count();
            if(!$inTeamIds){
                return $this->success('success');
            }
            $userId = $targerId;
        }

        $count = $this->app(UserCountService::class)->findByUid($userId);
        //团队业绩
        $poster['self_yeji']   = $count->self;
        $poster['team_yeji']   = $count->team;
        //团队本周USDT静入金
        $now = Carbon::now();$start = $now->startOfWeek()->format('Y-m-d');$ends = $now->endOfWeek()->format('Y-m-d');
        $poster['week_depos'] = $this->app(UserRechargeService::class)->getQuery()->where(['recharge_status'=>2,'order_coin'=>'usdt'])->whereBetween('created_at',[$start,$ends])->whereIn('user_id',function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->sum('order_mone');
        $poster['week_funds'] = $this->app(UserWithdrawService::class)->getQuery()->where(['withdraw_status'=>2,'order_coin'=>'usdt'])->whereBetween('created_at',[$start,$ends])->whereIn('user_id',function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->sum('order_mone');
        $poster['team_week'] = bcsub((string)$poster['week_depos'],(string)$poster['week_funds'],6);
        //团队入金
        $poster['team_depos'] = $this->app(UserRechargeService::class)->getQuery()->where(['recharge_status'=>2,'order_coin'=>'usdt'])->whereIn('user_id',function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->sum('order_mone');
        //团队出金
        $poster['team_funds'] = $this->app(UserWithdrawService::class)->getQuery()->where(['withdraw_status'=>2,'order_coin'=>'usdt'])->whereIn('user_id',function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->sum('order_mone');

        $poster['team_total'] = bcsub((string)$poster['team_depos'],(string)$poster['team_funds'],6);

        //自己今日入金
        $now = Carbon::now();$start = $now->startOfDay()->format('Y-m-d');$ends = $now->endOfDay()->format('Y-m-d');
        $poster['today_depos'] = $this->app(UserRechargeService::class)->getQuery()->where(['recharge_status'=>2,'order_coin'=>'usdt'])->whereDate('created_at',date('Y-m-d'))->where('user_id',$userId)->sum('order_mone');
        //自己今日出金
        $poster['today_funds'] = $this->app(UserWithdrawService::class)->getQuery()->where(['withdraw_status'=>2,'order_coin'=>'usdt'])->whereDate('created_at',date('Y-m-d'))->where('user_id',$userId)->sum('order_mone');
        //自己累计入金
        $poster['total_depos'] = $this->app(UserRechargeService::class)->getQuery()->where(['recharge_status'=>2,'order_coin'=>'usdt'])->where('user_id',$userId)->sum('order_mone');
        //自己累计出金
        $poster['total_funds'] = $this->app(UserWithdrawService::class)->getQuery()->where(['withdraw_status'=>2,'order_coin'=>'usdt'])->where('user_id',$userId)->sum('order_mone');

        return $this->success('success',$poster);
       
    }

    /**
     * 收益信息
     */
    public function spreadReward()
    {
        $userId = $this->request->query('userId');
        $reward = $this->app(UserRewardService::class)->findByUid($userId);
        //累计盈利
        //$poster['income'] = bcmul(strval($reward->income),'1',6);
        //累计亏损
        //$poster['deficit'] = bcmul(strval($reward->deficit),'1',6);
        //累计推广
        $poster['dnamic'] = bcmul(strval($reward->dnamic),'1',6);
        //累计团队
        $poster['groups'] = bcmul(strval($reward->groups),'1',6);
        //累计奖金
        //$poster['total_reward'] = bcadd($poster['dnamic'], $poster['groups'],6);
        //累计盈亏
        // $poster['total_profit_loss'] = bcsub($poster['income'] , $poster['deficit'],6);
        //今日奖金
        $now = Carbon::now();
        $start = date('Y-m-d H:i:s', $now->startOfDay()->timestamp);
        $ends  = date('Y-m-d H:i:s', $now->endOfDay()->timestamp);
        //$poster['income_today'] = $this->app(UserSecondIncomeService::class)->reward(['user_id'=>$userId,'reward_type'=>1,'timeStart'=>$start,'timeEnd'=>$ends]);
        //$poster['deficit_today'] = $this->app(UserSecondIncomeService::class)->reward(['user_id'=>$userId,'reward_type'=>2,'timeStart'=>$start,'timeEnd'=>$ends]);
        //$poster['dnamic_today'] = $this->app(UserSecondQuickenService::class)->reward(['user_id'=>$userId,'reward_type'=>1,'timeStart'=>$start,'timeEnd'=>$ends]);
        //$poster['groups_today'] = $this->app(UserSecondQuickenService::class)->reward(['user_id'=>$userId,'reward_type'=>2,'timeStart'=>$start,'timeEnd'=>$ends]);
        //今日奖金
        //$poster['total_reward'] = bcadd(strval($poster['dnamic_today']), strval($poster['groups_today']),6);
        //今日盈亏
        //$poster['profit_loss_today'] = bcsub(strval($poster['income_today']),strval($poster['deficit_today']),6);

        //两代内待结算动态
        $poster['dnamic_surplus'] = $this->app(UserSecondQuickenService::class)->reward(['user_id'=>$userId,'reward_type'=>1,'settle_time'=>0,'timeStart'=>$start,'timeEnd'=>$ends]);

         //两代外待结算团队
        //list($orderTotal,$start,$ends,$detailes) = $this->app(UserSecondService::class)->groupsCompute($userId);
        //$poster['order_total']  = $orderTotal;
        //$groupRate = $this->app(SysConfigService::class)->value('groups_rate');
        //$poster['groups_surplus'] = bcmul(strval($orderTotal),strval($groupRate/100),6);

        //今日流水
        $poster['liushui_today'] =  $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id', function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->where('reward_time','>=',strtotime($start) )->where('reward_time','<=',strtotime($ends))->sum('total');
        //本月流水
        $start_month = $now->startOfMonth()->timestamp; $ends_month = $now->endOfMonth()->timestamp;
        $poster['liushui_month'] =  $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id', function ($query) use($userId){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userId);
        })->where('reward_time','>=',$start_month)->where('reward_time','<',$ends_month)->sum('total');

        return $this->success('success',$poster);

    }

    public function send()
    {
        $data = $this->request->inputs(['scene','method']);
        $user = $this->service->find($this->request->query('userId'));
        $account_ip = intval( $this->app(SysConfigService::class)->value('account_ip'));
        if ($account_ip > 0){
            $login_ip = $this->app(UserService::class)->getQuery()->where('login_ip',$account_ip)->count();
            if($login_ip > $account_ip){
                return $this->fail('ip_fail');
            }
        }
        //数据验证
        if ($data['method'] == 1){
            $data['email'] = $user->email;
            $this->validated($data,\App\Validation\Api\EmsValidation::class);
            //轮询
            $emailServerList = $this->app(SysEmailService::class)->searchApi();
            if(0>=count($emailServerList)){
                return $this->fail('config_fail');
            }
            $emailServer = $emailServerList[0];
            if(!$emailServer){
                return $this->fail('config_fail');
            }
            $result = $this->app(EmsService::class)->send($this->request->input('scene'),$data['email'],$emailServer);
        }elseif ($data['method'] == 3){
            $data['area'] = $user->mobile_area;
            $data['mobile'] = $user->mobile;
            $this->validated($data,\App\Validation\Api\SmsValidation::class);
            $data['mobile'] = substr($data['mobile'],2,11);
            $result = $this->app(SmsService::class)->send($data['scene'] ,$data['area'] , $data['mobile']);
        }else{
            return $this->fail('scene_can_not_be_empty');
        }

        if($result !== true){
            return $this->fail('fail');
        }
        return $this->success('success');
    }
    /*领取体验账号、切换*/
    public function found()
    {
        $userId = $this->request->query('userId');
        $found = $this->app(UserFoundService::class)->getQuery()->where('found_id',$userId)->orWhere('user_id',$userId)->first();
        if(!$found){ //未激活模拟号、注册、登录
            $exists = $this->service->find($userId);
            if($exists->types == 3){
                return $this->fail('found_exists');//请使用正式号
            }
            //创建账号
            $entity = $this->service->found(['found_id'=>$userId]);
            //充值资产
            if(!$entity){
                return $this->fail('found_fail');//切换失败
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($entity->id);
            if($balance){
                $this->app(UserBalanceService::class)->rechargeTo($entity->id,strtolower('usdt'),$balance['usdt'],6000,18);
            }
            //登录
            $token = $this->app(ParseToken::class)->toToken($entity->id,$entity->username,'api');
            $result =   ['username'=>$entity->username,'token'=>$token['token']];
            //清除缓存
            $this->service->cachePutUserWhite();
            return $this->success('success',$result);
        }
        //已激活相互切换
        if($userId == $found->found_id){
            $entity = $this->service->find($found->user_id);
        }elseif($userId == $found->user_id){
            $entity = $this->service->find($found->found_id);
        }else{
            return $this->fail('found_fail');//切换失败
        }
        $token = $this->app(ParseToken::class)->toToken($entity->id,$entity->username,'api');
        $result =   ['username'=>$entity->username,'token'=>$token['token']];
        return $this->success('success',$result);
    }

    /*盈亏排行帮*/
    public function rank()
    {

        $where['types'] = 3;

        $limit = $this->request->input('limit',20);

        $index = $this->request->input('index',0);

        $sort = $this->request->input('sort','reward');

        $order = $this->request->input('order','desc');

        $lists = $this->app(UserRewardService::class)->searchRank($where,$limit,$sort,$order,$index);

        return $this->success('success',$lists);
    }

    /*首页统计*/
    public function statis()
    {
        $now = Carbon::now(); $startTime = $now->startOfDay()->format('Y-m-d H:i:s'); $endTime  = $now->endOfDay()->format('Y-m-d H:i:s');
        $startTimestamp = $now->startOfDay()->timestamp; $endTimestamp = $now->endOfDay()->timestamp;
        //交易额
        $number = Db::table('user_second')->when( $startTime != '' && $endTime != '', function ($query) use($startTime,$endTime){
            return $query->where('created_at','>=' ,$startTime)->where('created_at',"<",$endTime);
        })->whereNotIn('user_id',function ($query){
            return $query->select('id')->from('user')->where('types',3);
        })->whereNotIn('user_id',function ($query){
            return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
        })->sum('num');
        $statis_number = $this->app(SysConfigService::class)->value("statis_number");
        $lists['number'] = bcadd((string)$statis_number,bcmul((string)$number,'70',0),0);
        //盈利额
        $total = Db::table('user_second_income')->where('reward_time','>=' ,$startTimestamp)
            ->where('reward_time',"<",$endTimestamp)->whereNotIn('user_id',function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->whereNotIn('user_id',function ($query){
                return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
            })->where('reward_type',1)->sum('reward');
        $statis_total = $this->app(SysConfigService::class)->value("statis_total");
        $lists['total'] =   bcadd((string)$statis_total,bcmul((string)$total,'20',0),0);

        return $this->success('success',$lists);
    }






}
