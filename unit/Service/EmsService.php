<?php
declare(strict_types=1);

namespace Upp\Service;

use App\Common\Service\System\SysConfigService;
use Psr\SimpleCache\CacheInterface;
use Upp\Traits\HelpTrait;
use PHPMailer\PHPMailer\PHPMailer;

class EmsService
{
    use HelpTrait;

    private $expire_time= 900;

    private $drive = '';


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


    public function getKey($type,$phone)
    {
        return $this->getType()[$type] . $phone;
    }

    /**
     * 发送邮件
     * @return Bool
     */
    public function send($type,$email,$emailServer)
    {
        if (!array_key_exists($type, $this->getType())){
            return '场景错误';
        }
        //生成验证码
        $code = $this->createCode();
        $html = '<p>Hello, your verification code is：<em style="font-weight: 700;">'.$code.'</em></p>';
        $channel = new \Swoole\Coroutine\Channel();
        co(function() use ($channel,$email,$html,$emailServer) {
            $mail = new PHPMailer; //PHPMailer对象
            $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
            $mail->IsSMTP(); // 设定使用SMTP服务
            $mail->SMTPDebug = 0; // 关闭SMTP调试功能
            $mail->SMTPAuth = true; // 启用 SMTP 验证功能
            $mail->SMTPSecure = $emailServer['encryption']; // 使用安全协议 ssl
            $mail->Host = $emailServer['host'];// SMTP 服务器 smtpout.secureserver.net
            $mail->Port = $emailServer['port']; // SMTP服务器的端口号 '465'
            $mail->Username = $emailServer['username']; // SMTP服务器用户名 hi@ltnhtrnw.com
            $mail->Password = $emailServer['password']; // SMTP服务器密码 addqiuWREW9
            $mail->SetFrom($emailServer['address'], 'McnGlobale'); // 邮箱，昵称
            $mail->Subject = 'McnGlobale';
            $mail->MsgHTML($html);
            $mail->AddAddress($email); // 收件人
            $result = $mail->Send();
            if(!$result)
            {
                $result = $mail->ErrorInfo;
            }
            $channel->push($result);
        });
        $result = $channel->pop();
        if($result !== true){
            $error= ['file'=>'发送失败','msgs'=>$result];
            $this->logger('[发送邮件]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
             return '发送失败';
        }
        //短信发送成功之后，存储验证，并设置过期时间
        $res = $this->app(CacheInterface::class)->set($this->getKey($type,$email),$code, $this->expire_time);
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