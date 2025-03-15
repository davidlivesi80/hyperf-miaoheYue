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
namespace App\Controller\Api\Main;

use App\Common\Service\Push\PushMessageService;
use App\Common\Service\Subscribe\ChannelRecord;
use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\ChannelService;
use App\Common\Service\Users\UserRobotService;
use Upp\Basic\BaseController;
use Upp\Service\{BnbService, EmsService, PosterService, SmsService};
use App\Common\Service\Users\UserService;
use App\Common\Service\System\{SysArticleService,
    SysEmailService,
    SysImgsService,
    SysConfigService,
    SysVersionService,
    SysFilesService,
    SysCoinsService};
use Upp\Traits\HelpTrait;


class PublicController extends BaseController
{
    use HelpTrait;

    public function index(){

        return $this->success('发送成功');
    }

    public function reqToken()
    {
        $this->validated($this->request->all(),\App\Validation\Api\TokenValidation::class);
        $value =  $this->get_millisecond();
        $cache = md5('apitoken_second_' . $value);
        $this->getCache()->set($cache,$value, 86400);
        return $this->success('success', ['req-token'=>$cache,'req-expired'=>86400]);
    }

    public function reqDemos()
    {
//        $this->app(ChannelService::class)->invite(1,'btcusdt:1min');
//        $this->app(ChannelService::class)->invite(2,'btcusdt:1min');
//        $this->app(ChannelService::class)->invite(3,'btcusdt:1min');
//        $this->app(ChannelService::class)->invite(4,'btcusdt:1min');
//        $this->app(ChannelService::class)->invite(5,'btcusdt:1min');
//
//        $res = $this->app(ChannelService::class)->members('btcusdt:1min');
//
//        $rel = $this->app(ChannelService::class)->members('btcusdt:15min');

        return $this->success('success');
    }


    public function login()
    {

        $data = $this->request->inputs(['source','area','method','email','mobile','password']);
        //数据验证
        if($data['method'] == "mobile"){
            $account = $data['mobile'] = implode("",[$data['area'],$data['mobile']]);
            $this->validated($data,\App\Validation\Api\LoginMobileValidation::class);

        }else{
            $account = $data['email'];
            $this->validated($data,\App\Validation\Api\LoginEmailValidation::class);
        }
        $account_ip = intval( $this->app(SysConfigService::class)->value('account_ip'));
        if ($account_ip > 0){
            $login_ip = $this->app(UserService::class)->getQuery()->where('login_ip',$account_ip)->count();
            if($login_ip > $account_ip){
                return $this->fail('ip_fail');
            }
        }
        $result = $this->app(UserService::class)->doLogin($data['method'],$account,$data['password'],$this->request->query('ip'));
        return $this->success('login_success',$result);

    }

    public function regis()
    {
        $data = $this->request->inputs(['source','area','method','email','mobile','password','password_confirmation','parent','code']);
        $data['code'] = trim($data['code']);
        //数据验证
        if($data['method'] == "mobile"){
            $data['mobile'] = implode("",[$data['area'],$data['mobile']]);
            $this->validated($data,\App\Validation\Api\RegisMobileValidation::class);
            //验证
            $result = $this->app(SmsService::class)->check('register',$data['mobile'],$data['code']);
            if($result !== true){
                return $this->fail('verify_fail');
            }
        }else{
            $this->validated($data,\App\Validation\Api\RegisEmailValidation::class);
            // 定义常规邮箱后缀
            $validDomains = explode('@',$this->app(SysConfigService::class)->value('user_regis_email'));
            // 提取邮箱后缀
            $domain = substr(strrchr($data['email'], "@"), 1);
            if(!in_array($domain, $validDomains)){
                return $this->fail('email_wrong_format');
            }
            //验证
            $result = $this->app(EmsService::class)->check('register',$data['email'],$data['code']);
            if($result !== true){
                return $this->fail('verify_fail');
            }
        }

        $register_ip = intval( $this->app(SysConfigService::class)->value('register_ip'));
        if ($register_ip > 0){
            $regis_ip = $this->app(UserService::class)->getQuery()->where('regis_ip',$register_ip)->count();
            if($regis_ip > $register_ip){
                return $this->fail('ip_fail');
            }
        }
        $res = $this->app(UserService::class)->create($data,$this->request->query('ip'));
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success');

    }

    public function forget()
    {

        $data = $this->request->inputs(['method','area','mobile','email','code','password','password_confirmation']);
        //数据验证
        if($data['method'] == "mobile"){
            $account = $data['mobile'] = implode("",[$data['area'],$data['mobile']]);
            $this->validated($data,\App\Validation\Api\ForgetMobileValidation::class);
            //验证
            $result = $this->app(SmsService::class)->check('forget',$account,$data['code']);
            if($result !== true){
                return $this->fail('verify_fail');
            }
        }else{
            $account = $data['email'];
            $this->validated($data,\App\Validation\Api\ForgetEmailValidation::class);
            //验证
            $result = $this->app(EmsService::class)->check('forget',$account,$data['code']);
            if($result !== true){
                return $this->fail('verify_fail');
            }
        }

        $this->app(UserService::class)->forget($data['method'],$account,$data['password'],$this->request->query('ip'));

        return $this->success('success');

    }

    public function sendSms()
    {

        $data = $this->request->inputs(['area','scene','mobile']);
        //数据验证
        $this->validated($data,\App\Validation\Api\SmsValidation::class);
        $register_ip = intval( $this->app(SysConfigService::class)->value('register_ip'));
        if ($register_ip > 0){
            $regis_ip = $this->app(UserService::class)->getQuery()->where('regis_ip',$register_ip)->count();
            if($regis_ip > $register_ip){
                return $this->fail('ip_fail');
            }
        }
        $result = $this->app(SmsService::class)->send($data['scene'] ,$data['area'] , $data['mobile']);
        if($result !== true){
            return $this->fail('fail',$result);
        }
        return $this->success('success');

    }

    public function sendEms()
    {
         $data = $this->request->inputs(['scene','email']);
         //数据验证
         $this->validated($data,\App\Validation\Api\EmsValidation::class);
         $register_ip = intval( $this->app(SysConfigService::class)->value('register_ip'));
         if ($register_ip > 0){
             $regis_ip = $this->app(UserService::class)->getQuery()->where('regis_ip',$register_ip)->count();
             if($regis_ip > $register_ip){
                return $this->fail('ip_fail');
             }
         }

         //轮询
         $emailServerList = $this->app(SysEmailService::class)->searchApi();
         if(0>=count($emailServerList)){
             return $this->fail('config_fail');
         }
         $emailServer = $emailServerList[0];
         if(!$emailServer){
             return $this->fail('config_fail');
         }
         $result = $this->app(EmsService::class)->send($this->request->input('scene'),$this->request->input('email'),$emailServer);
         if($result !== true){
            return $this->fail('fail',$result);
         }
         return $this->success('success');
    }

    //滑块验证\输入、旋转
    public function getCaptcha()
    {
        $result = $this->app(PosterService::class)->getInputCaptcha();

        return $this->success('success',$result);

    }



}
