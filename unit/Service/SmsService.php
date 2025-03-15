<?php
declare(strict_types=1);

namespace Upp\Service;

use Hyperf\Cache\Annotation\Cacheable;
use Psr\SimpleCache\CacheInterface;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

class SmsService
{
    use HelpTrait;

    private $expire_time= 900;

    private $sms_tpl = "6026844";
    private $sms_pass = 'bcefd0d043956ad1036554bafa03ddfc';//


    public function __construct( $driverClass = "SmsYun")
    {
        $this->drive =  make('\\Upp\\Service\\Sms\\Storage\\'.$driverClass);
    }

    public function getType()
    {
        return [
            'login'	=> 'login_code_',//登录
            'register'	=> 'register_code_',//注册
            'forget'  => 'forget_code_',//登录、支付密码
            'bind'	=> 'bind_code_',//绑定、验证手机
            'other'	=> 'other_code_',//提现互转
            'sms_tip'=> 'sms_tip_code_',//提现充值通值
        ];
    }

    /**
     * @Cacheable(prefix="sms", ttl=900, listener="sms-update")
     */
    public function getKey($type,$phone)
    {
        return $this->getType()[$type] . $phone;
    }

    /**
     * 发送短信
     * @return mixed
     */
    public function send($type,$area,$phone)
    {
        if (!array_key_exists($type, $this->getType())){
            return '场景错误';
        }
        //生成验证码
        $code = $this->createCode();
        //发送短信
        if($area == 86){
            $result = $this->drive->sendCodeChind($this->sms_tpl,$this->sms_pass, $phone, $code);
        }else{
            $result = $this->drive->sendCode($this->sms_tpl,$this->sms_pass , '+' . $area . $phone, $code);
        }
        if($result !== true){
            $error= ['file'=>'发送失败','msgs'=>$result];
            $this->logger('[发送短信]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return '发送失败';
        }
        //短信发送成功之后，存储验证，并设置过期时间
        $res = $this->app(CacheInterface::class)->set($this->getKey($type,$area.$phone),$code, $this->expire_time);
        if(!$res){
            return '缓存失败';
        }
        return true;

    }

    /**
     * 生成验证码
     * @return int
     */
    private function createCode()
    {
        return rand(100000, 999999);
    }

    /**
     * 短信验证
     * @return bool
     */
    public function check($type,$phone,$code)
    {
        $register_code = $this->app(SysConfigService::class)->value('register_code');
        if ($code == $register_code) {
            return true;
        }

        $cache = $this->app(CacheInterface::class)->get($this->getKey($type,$phone));

        if ($code == $cache) {
            //清除缓存
            $this->app(CacheInterface::class)->delete($this->getKey($type,$phone));
            return true;
        }

        return false;
    }

}