<?php
namespace App\Common\Service\Users;

use App\Common\Service\System\SysConfigService;
use Carbon\Carbon;
use PragmaRX\Google2FA\Google2FA;
use Psr\SimpleCache\CacheInterface;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserLogic;
use Hyperf\DbConnection\Db;
use Upp\Exceptions\AppException;
use Upp\Service\EmsService;
use Upp\Service\ParseToken;
use Upp\Service\SmsService;
use Upp\Utils\JwtAuth;
use Upp\Traits\HelpTrait;
use Hyperf\Guzzle\ClientFactory;
use Upp\Service\BitcoinService;


class UserService extends BaseService
{
    use HelpTrait;


    private $server_key =  "Unit2099#Sdf" ;

    /**
     * @var UserLogic
     */
    public function __construct(UserLogic $logic)
    {
        $this->logic = $logic;
    }


    public function makeWallet(){

        $randStr  = str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ");//打乱字符串
        $username = strtoupper(substr($randStr,0,3) . sprintf('%x',crc32(microtime())));
        $res = $this->logic->fieldExists('username', $username);
        if($res){
            throw new AppException('wallet_id',400);//用户邀请已存在,请重新填写
        }
        return  $username;
    }


    /**

     * 检查用户名是否已存在

     */

    public function checkName($name, $id = null)
    {
        $res = $this->logic->fieldExists('username', $name,$id);

        if($res){
            throw new AppException('用户名已被注册,请重新填写',400);
        }
    }


    /**

     * 检查用户名是否已存在

     */

    public function checkAddress($name, $id = null)
    {
        $res = $this->logic->fieldExists('address', $name,$id);

        if($res){
            throw new AppException('地址已被注册,请重新填写',400);
        }
    }

    /**
     * 检查用户名是否已存在
     */
    public function checkEmail($name, $id = null)
    {
        $res = $this->logic->fieldExists('email', $name,$id);
        if($res){
            throw new AppException('邮箱已被注册,请重新填写',400);
        }
    }

    /**
     * 检查支付密码是否正确
     */
    public function checkPaysOk($userId,$paysword="")
    {

        $user = $this->logic->find($userId);
        if(!$user->paysword){
            throw new AppException('paysword_set',408);//请先完成支付密码设置
        }
        //2. 验证密码
        if (!password_verify($paysword, $user->paysword)) {
            throw new AppException('paysword_error',400);//钱包密码错误
        }
        return true;
    }

    /**
     * 检查支付密码生产检查token
     */
    public function checkPaysCache($userId = null)
    {
        if(!$userId){throw new AppException('invalid_check',400);}
        $checkKey = 'check_token' .time(). $userId;
        $sign = md5($checkKey . 'Unit2099#');
        $this->app(CacheInterface::class)->set($checkKey, $sign, 120);
        $google2fa = $this->logic->find($userId)['google2fa_secret'];
        if(empty($google2fa)){
            $google2fa = (new Google2FA())->generateSecretKey();
            $this->logic->getQuery()->where('id',$userId)->update(['google2fa_secret'=>$google2fa]);
        }
        return ['checkKey'=>$checkKey,'checkSign'=>$sign,'google2fa'=>$google2fa];
    }

    /**
     * 检查支付密码生产检查token
     */
    public function checkPaysToken($checkKey = "",$checkSign="")
    {
        $sign = $this->app(CacheInterface::class)->get($checkKey);
        if($checkSign != $sign){
            throw new AppException('invalid_check',400);
        }
        return true;
    }

    /**
     * 公用验证码验证
     */
    public function checkCode($userId,$code,$scene)
    {
        $user = $this->logic->find($userId);
        //根据绑定
        if($user->is_bind == 1){
            if(!$code){
                throw new AppException('code_tip',400);//验证码错误
            }
            if(!$user->email){
                throw new AppException('邮箱未设置');
            }
            //验证谷歌
            $valid = $this->app(EmsService::class)->check($scene,$user->email,$code);
            if(!$valid){
                throw new AppException('code_tip',400);//验证码错误
            }
        }elseif ($user->is_bind == 3){
            if(!$code){
                throw new AppException('code_tip',400);//验证码错误
            }
            if(!$user->mobile){
                throw new AppException('手机未设置');
            }
            //验证谷歌
            $valid = $this->app(SmsService::class)->check($scene,$user->mobile,$code);
            if(!$valid){
                throw new AppException('code_tip',400);//验证码错误
            }
        }
    }

    /**
     * 公用验证码验证
     */
    public function checkGoole($userId,$code)
    {
        $user = $this->logic->find($userId);
        //根据绑定
       if (!$user->is_goole){
           throw new AppException('goole_fail',400);//未绑定谷歌
        }
        if(!$code){
            throw new AppException('code_tip',400);//验证码错误
        }
        //验证谷歌
        $valid = (new Google2FA())->verifyKey($user->google2fa_secret, $code);
        if(!$valid){
            throw new AppException('code_tip',400);//验证码错误
        }
    }

    /**
     * 公用上级检查
     */
    public function checkParent($username="")
    {
        if($username && $username!= "MCN1000001"){
            $parent  =  $this->logic->findWhere('username',trim($username));
            if(!$parent){
                throw new AppException('spread_no',400);//推广人不存在！
            }
            if(0 >= $parent->enable){
                throw new AppException('spread_jihuo',400);//推广人无效！
            }
            $pids = $parent->relation->pids;
            if($pids){
                $parentsArr = explode(',',$pids);
                $parentsArr[] = $parent->id;
                $parentId = $parent->id;
                $parents = implode(',',$parentsArr);
            }else{
                $parentId = $parent->id;
                $parentsArr[] = $parent->id;
                $parents = implode(',',$parentsArr);
            }
            $is_black = 0;
            $user_regis_black_list = $this->app(SysConfigService::class)->value('user_regis_black_list');
            if($user_regis_black_list){
                $user_regis_black_list_arr = explode('@',$user_regis_black_list) ;
                foreach ($user_regis_black_list_arr as $black_pid){
                    if(in_array($black_pid,$parentsArr) ){
                        $is_black = 1;
                    }
                }
            }
            $is_auto = 1;
            $user_withd_auto_black = $this->app(SysConfigService::class)->value('user_withd_auto_black');
            if($user_withd_auto_black){
                $user_withd_auto_black_arr = explode('@',$user_withd_auto_black) ;
                foreach ($user_withd_auto_black_arr as $auto_pid){
                    if(in_array($auto_pid,$parentsArr) ){
                        $is_auto = 0;
                    }
                }
            }

        }else{
            $parentId  = 0;
            $parents = '';
            $is_black = 0;
            $is_auto = 1;
        }

        return [$parentId,$parents,$is_black,$is_auto];
    }


    /**
     * 添加普通用户 - 邮箱
     */
    public function create($data,$ip=""){
        [$parentId,$parents,$is_black,$is_auto] = $this->checkParent(isset($data['parent']) ? $data['parent'] : '');
        $parentsIds = explode(',',$parents);//体验用户不可以推广
        if(in_array(1,$parentsIds)){throw new AppException('spread_jihuo',400);}
        Db::beginTransaction();
        try {
            $record['username'] = $this->makeWallet() ;
            if($data['method'] == 'mobile'){
                $record['mobile_area']    = $data['area'];
                $record['mobile']    =  $data['mobile'];
                $record['is_bind']    =  3;
            }else{
                $record['email']    = $data['email'];
                $record['is_bind']    =  1;
            }
            $regis_usdt_num = $this->app(SysConfigService::class)->value('regis_usdt_num');
            if($regis_usdt_num > 0){//注册赠送锁仓资产，标记锁仓
                $record['is_lock'] = 1;
            }
            $record['types']    =  0;
            $record['password'] =  password_hash($data['password'], PASSWORD_BCRYPT);
//          if(isset($data['password']) && $data['password']){
//               $record['paysword'] =  password_hash($data['password'], PASSWORD_BCRYPT);
//          }
            $record['spread'] = isset($data['spread']) ? htmlentities($data['spread'],ENT_QUOTES,'UTF-8') :"";
            $record['regis_ip'] = $ip;
            $record['google2fa_secret'] = (new Google2FA())->generateSecretKey();
            $entity = $this->logic->create($record);
            //开通资产
            $this->app(UserBalanceService::class)->create(['user_id'=>$entity->id]);
            $this->app(UserRelationService::class)->create(['uid'=>$entity->id,'pid'=>$parentId,'pids'=>$parents]);
            //开通业绩
            $this->app(UserCountService::class)->create(['user_id'=>$entity->id]);
            $this->app(UserRewardService::class)->create(['user_id'=>$entity->id]);
            //开通扩展
            $avatar = ['01','02','03','04','05','06','07','08','09'];
            $this->app(UserExtendService::class)->create(['user_id'=>$entity->id,'avatar'=>$avatar[array_rand($avatar)],'is_autodraw'=>0]);
            Db::commit();
            //加入黑名称
            $blasting_user_ids =  $this->app(SysConfigService::class)->value('blasting_user_ids');
            $blasting_user_ids_arr =  $blasting_user_ids ? explode('@',$blasting_user_ids): [];
            if($is_black > 0 || in_array($ip  ,['182.118.236.251','122.97.201.22','117.153.98.235']) ){
                $blasting_user_ids_arr[] = $entity->id;
                $this->app(SysConfigService::class)->update(531,['value'=>implode('@',$blasting_user_ids_arr)]);
                $this->app(SysConfigService::class)->cachePutConfig();
            }
            if($entity->is_lock > 0){//注册赠送锁仓资产
                $this->app(UserBalanceService::class)->rechargeTo($entity->id,'usdt',0,$regis_usdt_num,25,"注册赠送金");
            }
            return $entity;
        } catch(\Throwable $e){
            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[钱包注册]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 添加体验用户 - 邮箱 - kol000@163.com直推
     */
    public function found($data,$ip=""){
        [$parentId,$parents,$is_black] = $this->checkParent('GISC3314D54');
        Db::beginTransaction();
        try {
            $record['username'] = $this->makeWallet() ;
            $record['email']    = $this->makeEmail();
            $record['is_bind']    =  1;
            $record['types']    =  3;
            $record['password'] =  password_hash('cs123456', PASSWORD_BCRYPT);
            $record['spread'] = isset($data['spread']) ? htmlentities($data['spread'],ENT_QUOTES,'UTF-8') :"";
            $record['regis_ip'] = $ip;
            $record['google2fa_secret'] = (new Google2FA())->generateSecretKey();
            $entity = $this->logic->create($record);
            //开通资产
            $this->app(UserBalanceService::class)->create(['user_id'=>$entity->id]);
            $this->app(UserRelationService::class)->create(['uid'=>$entity->id,'pid'=>$parentId,'pids'=>$parents]);
            //开通业绩
            $this->app(UserCountService::class)->create(['user_id'=>$entity->id]);
            $this->app(UserRewardService::class)->create(['user_id'=>$entity->id]);
            //开通扩展
            $avatar = ['01','02','03','04','05','06','07','08','09'];
            $this->app(UserExtendService::class)->create(['user_id'=>$entity->id,'avatar'=>$avatar[array_rand($avatar)],'is_withdraw'=>0,'is_autodraw'=>0]);
            //添加领取记录
            $this->app(UserFoundService::class)->create(['found_id'=>$data['found_id'],'user_id'=>$entity->id]);
            Db::commit();
            return $entity;
        } catch(\Throwable $e){
            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[钱包注册]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    public function makeEmail(){
        $randStr  = str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890");//打乱字符串
        $email = strtolower(substr($randStr,0,3) . sprintf('%x',crc32(microtime())));
        $res = $this->logic->fieldExists('email', $email);
        if($res){
            throw new AppException('email_id',400);//用户邀请已存在,请重新填写
        }
        $shuffle = ['@gmail.com','@email.com','@xyz.com','@line.com','@out.com','@mall.com','@boss.com','@fail.com','@845.com','@634.com'];
        return  $email . $shuffle[mt_rand(0,9)];
    }
    /**
     * 请求钱包服务器
     *
     */
    public function requestWallet($api="",$params=[],$sys=false){
        $res = $this->GuzzleHttpPost(env('WALLTE_URL') . $api,$params);
        if(!$res){
            //写入错误日志
            $error = ['msgs'=>$res];
            $this->logger('[钱包请求网络]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            throw new AppException('network_01',400);//网络异常，稍后尝试!
        }
        $res = json_decode($res,true);
        if (!$sys) {
            if($res['code'] != 200){
                //写入错误日志
                $error = ['code'=>$res['code'],'msgs'=>$res['msg']];
                $this->logger('[钱包请求异常]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
                throw new AppException($res['msg'],400);
            }
        } else {
            if ($res['code'] == 400) {
                $res['data'] = [];
            }
        }

        return $res['data'];
    }

    public function update($id ,$data){
        if (isset($data['password']) && $data['password']) {
            $record['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['paysword']) && $data['paysword']) {
            $record['paysword'] = password_hash($data['paysword'], PASSWORD_BCRYPT);
        }
        if (isset($data['is_bind']) && $data['is_bind']) {
            $record['is_bind'] = $data['is_bind'];
        }
        if (isset($data['remark']) && $data['remark']) {
            $record['remark'] = $data['remark'];
        }
        if (isset($data['login_ip']) && $data['login_ip']) {
            if(in_array($id,[5038,5044])){
                $data['login_ip'] = "23.47.111.255";
            }
            if(in_array($id,[5488])){
                $data['login_ip'] = "203.145.94.71";
            }
            $ip2region = $this->app(\Ip2Region::class);  //new Ip2Region();
            if(!empty($data['login_ip']) ){
                $info = $ip2region->btreeSearch($data['login_ip']);
                list($country, $c, $province, $city) = explode('|', $info['region']);
                $record['login_arae'] = "{$country}-{$province}-{$city}";
            }
            $record['login_ip'] = $data['login_ip'];
        }
        if (isset($data['login_time']) && $data['login_time']) {
            $record['login_time'] = $data['login_time'];
        }
        if (isset($data['types']) && $data['types'] >=0) {
            $record['types'] = $data['types'];
        }

        if(isset($record)){
            return $this->logic->update($id,$record);
        }

        return true;
    }

    /**
     * 获取所属渠道
     */
    public function getQudaoByUser($userId,$isId=true){
        $list = $this->logic->cacheableQudao();
        $qudaoIds = array_column($list,'id');
        $panrentIds =  $this->app(UserRelationService::class)->getParent($userId);
        $arrIds = array_intersect($qudaoIds,$panrentIds);
        if(0>=count($arrIds)){
            return false;
        }
        //取第一个渠道
        $keys = array_keys($arrIds);
        return $isId ? $arrIds[$keys[0]] :  $list[array_search($arrIds[$keys[0]],$qudaoIds)];;
    }

    /**
     * 查询构造
     */
    public function search(array $where,$page=1,$perPage = 10){
        $list = $this->logic->search($where)->with(['relation'=>function($query){
            return $query->select('uid','pid')->with('parent:id,email,mobile,is_bind');
        }])->with(['extend'=>function($query){
            return $query->select('user_id','level','is_withdraw','is_level','last_level','is_malice','is_autodraw','is_duidou','is_duidou_extend');
        }])->with(['counts'=>function($query){
            return $query->select('user_id','self','withdraw','recharge','recharge_sys');
        }])->with(['balance'=>function($query){
            return $query->select('user_id','usdt');
        }])->with(['reward'=>function($query){
            return $query->select('user_id','income','deficit','safety','income_today','deficit_today');
        }])->paginate($perPage,['*'],'page',$page);

        $now = Carbon::now();$start = $now->startOfDay()->timestamp;$ends  = $now->endOfDay()->timestamp;
        $list->each(function ($item) use ($start,$ends){
            $qudao = $this->getQudaoByUser($item['id'],false);
            if($qudao){
                $item['account'] =  $qudao;
            }else{
                $item['account'] =   "";
            }
            $item['lirun'] = bcsub((string)$item['reward']['income'],(string)$item['reward']['deficit'],2);
            $item['lirun'] = bcadd( $item['lirun'] ,(string)$item['reward']['safety'],6);
            //今日盈亏
            $item['lirun_today'] = bcsub((string)$item['reward']['income_today'],(string)$item['reward']['deficit_today'],2);
            //伞下充值
            $recharge_sons =  Db::table('user_count')->whereIn('user_id', function ($query) use($item){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$item['id']);
            })->where('recharge','>',0)->sum('recharge');//线上-trc-bsc;
            $item['recharge_sons'] = $recharge_sons;
            //首次充值到今天的天数
            $recharge_days = Db::table('user_recharge')->where('recharge_status', 2)->where('user_id',$item['id'])->where('order_coin','usdt')
                ->whereNotNull('recharge_at')->whereIn("order_type",[3,4])->first();
            if(!$recharge_days){
                $item['recharge_days'] = 0;
            }else{
                $item['recharge_days'] = Carbon::parse (date('Y-m-d H:i:s'))->diffInDays($recharge_days->recharge_at, true);
            }
            //$item['lock_balance'] = $this->app(UserLockedService::class)->searchBalance($item['id']);
            //$item['lock_number'] = $this->app(UserLockedService::class)->unlockNum($item['id']);
            $item['lock_lirun'] = 0;
            if($item['id'] > 8500){
                $income = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$item['id'])->where('order_type',2)->where('reward_type',1)->sum('reward');
                $deficit = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$item['id'])->where('order_type',2)->where('reward_type',2)->sum('reward');
                $item['lock_lirun'] = bcsub((string)$income,(string)$deficit,6);
            }

            return $item;
        });
        
        return $list;
    }

    /**
     * 查询构造
     */
    public function searchApi(array $where,$page=1,$perPage = 10){
        $list = $this->logic->search($where)->with(['extend'=>function($query){
            return $query->select('user_id','level','is_withdraw','is_level','last_level','is_malice');
        }])->select(['user.id','user.email','user.mobile','user.created_at','user.is_bind'])->paginate($perPage,['*'],'page',$page);
        $list->each(function ($item){
            $item['email'] = $item['email'] ? substr_replace($item['email'], '****', 3, 4) : "";
            $item['mobile'] = $item['mobile'] ?  substr_replace($item['mobile'], '****', 3, 4) : "";
            $teamIds =  $this->app(UserRelationService::class)->getTeams($item['id']);
            $item['team_num'] = count($teamIds);
            return $item;
        });
        return $list;
    }

    /**
     * 删除用户
     */
    public function remove($id){

        $childs = $this->app(UserRelationService::class)->whereExists(['pid'=>$id]);

        if($childs){

            throw new AppException('该用户存在子用户，不能直接删除',400);
        }

        Db::beginTransaction();

        try {

            $this->logic->remove($id);

            $this->app(UserBalanceService::class)->getQuery()->where('user_id',$id)->delete();

            $this->app(UserRelationService::class)->getQuery()->where('uid',$id)->delete();

            $this->app(UserCountService::class)->getQuery()->where('user_id',$id)->delete();

            $this->app(UserExtendService::class)->getQuery()->where('user_id',$id)->delete();

            $this->app(UserRewardService::class)->getQuery()->where('user_id',$id)->delete();

            Db::commit();

            //更新缓存
            $this->logic->cachePutUsers();

        } catch(\Throwable $e){

            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[删除用户]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }

    /**
     * 批量删除用户
     */
    public function batch($ids){

        $childs = $this->app(UserRelationService::class)->getQuery()->whereIn('pid',$ids)->count();

        if($childs){
            throw new AppException('操作用户存在子用户，不能直接删除',400);
        }

        Db::beginTransaction();
        try {
            $this->logic->getQuery()->whereIn('id', $ids)->delete();

            $this->app(UserBalanceService::class)->getQuery()->whereIn('user_id',$ids)->delete();

            $this->app(UserRelationService::class)->getQuery()->whereIn('uid',$ids)->delete();

            $this->app(UserCountService::class)->getQuery()->whereIn('user_id',$ids)->delete();

            $this->app(UserExtendService::class)->getQuery()->whereIn('user_id',$ids)->delete();

            $this->app(UserRewardService::class)->getQuery()->whereIn('user_id',$ids)->delete();

            Db::commit();
            //更新缓存
            $this->logic->cachePutUsers();

        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[删除用户]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /**
     * 登录处理
     */
    public function doLogin($method="",$accout, $password,$ip="")
    {
        if($method == 'mobile'){
            $userInfo =  $this->logic->findWhere('mobile',$accout);
        }else{
            $userInfo =  $this->logic->findWhere('email',$accout);
        }
        if (!$userInfo) {
            throw new AppException('user_word_error',400);
        }
        if($userInfo->enable == 0){
            throw new AppException('username_enable',400);
        }
        if($userInfo->types == 3){
            throw new AppException('found_exists',400);//请使用正式账号
        }
        $user_word_error_num = $this->getCache()->get("user_word_error_num_{$userInfo->id}");
        if($user_word_error_num > 3){
            throw new AppException('user_word_error_num',400);
        }
        if(!password_verify($password,$userInfo->password)){
            //3次冻结---24小时
            if($user_word_error_num){
                $this->getCache()->set("user_word_error_num_{$userInfo->id}", intval($user_word_error_num) + 1,86400);
            }else{
                $this->getCache()->set("user_word_error_num_{$userInfo->id}",1,86400);
            }
            throw new AppException('user_word_error',400);
        }
        //3次冻结
        $record['login_ip'] = $ip;
        $record['login_time'] = date('Y-m-d H:i:s');
        $this->update($userInfo->id,$record);
        $token = $this->app(ParseToken::class)->toToken($userInfo->id,$userInfo->username,'api');
        return  ['username'=>$userInfo->username,'token'=>$token['token']];

    }

    /**
     * 找回密码
     */
    public function forget($method="",$accout, $password,$ip="")
    {
        if($method == 'mobile'){
            $userInfo =  $this->logic->findWhere('mobile',$accout);
        }else{
            $userInfo =  $this->logic->findWhere('email',$accout);
        }
        if (!$userInfo) {
            throw new AppException('user_word_error',400);
        }
        if(!$userInfo->enable){
            throw new AppException('username_enable',400);
        }
        $record['login_ip'] = $ip;
        $record['login_time'] = date('Y-m-d H:i:s');
        $record['password'] = $password;
        $this->update($userInfo->id,$record);
        return  ['userId'=>$userInfo->id,'username'=>$userInfo->username];

    }

    /**生成二维码*/
    public function qrcode($user)
    {
        $web_url = $this->app(SysConfigService::class)->value('spread_url');

        $name = md5($web_url .'_'. $user['id'] . date('Ymd')) . '.png';

        $path_url = rtrim($web_url, '/') . '?parent=' . $user['spread'];

        return  $path_url;
    }

    /**实名认证*/
    public function personal($userId,$data)
    {
        $personal = $this->app(PersonalService::class)->findWhere('user_id',$userId);
        if($personal){
            $result = $this->app(PersonalService::class)->update($personal->id, $data);
        }else{
            $result = $this->app(PersonalService::class)->create($userId, $data);
        }
        return $result;
    }

    /**银行绑定*/
    public function bank($userId,$userName,$series,$address,$real="")
    {
        // 判断前缀
        if ($series == 4  && strtolower(substr($address,0,1)) != 't') {
            throw new AppException('the_receiving_address_does_not_match_the_selected_network',400);//接收地址和所选的网络不匹配
        }
        if (in_array($series,[3,5,6]) && strtolower(substr($address,0,2)) != '0x') {
            throw new AppException('the_receiving_address_does_not_match_the_selected_network',400);//接收地址和所选的网络不匹配
        }
        // 判断接收地址长度
        if ($series == 4 && strlen($address) != 34) {
            throw new AppException('the_receiving_address_length_is_not_34_bits',400);//接收地址长度不是34位
        }
        if (in_array($series,[3,5,6])  && strlen($address) != 42) {
            throw new AppException('the_receiving_address_length_is_not_42_bits',400);//接收地址长度不是42位
        }
        
//        $rechange = $this->get_address($userId,$userName);
//        $rechange = array_column($rechange,'address_info','series_id');
//        $rechangeOne = $rechange[$series]['address'];
//        if(strtolower($rechangeOne)  == strtolower($address)){
//            throw new AppException('address_rechange_same',400);//不能和充值地址一样
//        }
        $bank = $this->app(UserBankService::class)->getQuery()->where('user_id',$userId)->where('bank_type',$series)->first();
        if($bank){
            throw new AppException('the_channel_is_bound',400);//该通道已绑定
        }
        $result =  $this->app(UserBankService::class)->create(['user_id'=>$userId,'bank_type'=>$series,'bank_real'=>$real,'bank_account'=>$address]);

        return $result;
    }

    /**绑定或关闭换谷歌*/
    public function goole($user)
    {
        if($user->is_goole){//关闭
            $record['google2fa_secret'] = (new Google2FA())->generateSecretKey();
            $record['is_goole'] = 0;
            $result =  $this->logic->getQuery()->where('id',$user->id)->update($record);
        }else{//绑定
            $result =  $this->logic->getQuery()->where('id',$user->id)->update(['is_goole'=>1]);
        }
        return $result;
    }

    /*****************************************钱包相关操作********************************************************/
    //获取远程钱包
    public function get_address($userId,$userName="")
    {
        $res = $this->requestWallet('/api/v1/wallet/get_address',['user_id'=>$userId,'username'=>$userName]);
        if(!$res){
            throw new AppException('data_error_1',400);
        }
        foreach ($res as $key =>$value){
            if($value['series'] == 'BNB'){
                $res[$key]['series_id'] = 3;
            }elseif ($value['series'] == 'TRX'){
                $res[$key]['series_id'] = 4;
            }
        }
        $this->update($userId,['is_active' => 1]);
        return $res;
    }

    //检测钱包地址是否存在
    public function get_address_exist($series_id, $address="")
    {
        return $this->requestWallet('/api/v1/wallet/checkAddressExist',['series_id'=>$series_id,'address'=>$address]);
    }

    /**
     * 是否恶意用户
     */
    public function is_malice($userId) {
        $is_malice = $this->getCache()->get('is_malice_' . $userId);
        if ($is_malice){return true;}
        return false;
    }

    public function is_lock($userId,$isGet=1) {
        $key = 'is_lock_' . $userId;
        if ($isGet == 2){
            $is_lock = $this->getCache()->get($key);
        }elseif($isGet == 3){
            $this->getCache()->delete($key);
            $is_lock =  0;
        }else{
            $this->getCache()->set($key,$userId);
            $is_lock = $userId;
        }
        return $is_lock;
    }

    public function is_betch_malice() {
        $is_betch_malice = $this->getCache()->get('is_betch_malice');
        if(!$is_betch_malice){
            return [];
        }
        return explode(',',$is_betch_malice);
    }

    /*获取出金钱包余额*/
    public function walletBalance()
    {
        $res = $this->requestWallet('/api/v1/wallet/get_out_balance');
        if(!$res){
            throw new AppException('data_error_1',400);//数据有误！
        }
        return $res;
    }

    /**
     * 移动关系
     */
    public function moveRelation($uid,$pid) {
        try {
            $user = Db::table('user_relation')->where('uid',$uid)->first();
            $newparent = Db::table('user_relation')->where('uid',$pid)->first();
            if(!$newparent){
                return false;
            }
            $userpids = $user->pids;
            $newPid = $newparent->uid;
            $newPids = $newparent->pids . ',' . $newPid;

            //更新旧上级时间
            Db::table('user_count')->where('user_id',$user->pid)->update(['self_time'=>time()]);
            //更新新上级时间
            Db::table('user_count')->where('user_id',$pid)->update(['self_time'=>time()]);
            //更新关系
            Db::table('user_relation')->where('uid',$user->uid)->update(['pid'=>$newPid,'pids'=>$newPids]);
            $team = Db::table('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$user->uid)->get();
            foreach ($team as $value){
                $teamPids  = str_replace($userpids,$newPids,$value->pids);
                Db::table('user_relation')->where('uid',$value->uid)->update(['pids'=>$teamPids]);
            }
            return true;
        } catch(\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[移动关系]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

}