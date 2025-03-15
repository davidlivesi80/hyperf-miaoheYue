<?php
/*短信宝*/
declare(strict_types=1);

namespace Upp\Service\Sms\Storage;

class SmsBao
{

    public function sendCode($sms_user,$sms_pass,$phone,$code)
    {
        if(!$sms_user || !$sms_pass){
            return "400";
        }

        $temp = '您的验证码为'.$code.',在15分钟内有效';

        $url  = "http://www.smsbao.com/sms?u=".$sms_user."&p=".$sms_pass."&m=".$phone."&c=".urlencode($temp);

        $ch = curl_init();
        $timeout = 10;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        if($file_contents == '0'){
            return true;
        }else{
            return $file_contents;
        }
    }

    public function getError($result)
    {
        $statusStr = array(
            "30" => "短信宝密码错误",
            "40" => "短信宝账号非法",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词",
            "51" => "手机号码不正确",
            "400"=> "参数错误",
        );

        return isset($statusStr[$result]) ? $statusStr[$result] : '未知错误';
    }
}