<?php
/*短信宝*/
declare(strict_types=1);

namespace Upp\Service\Sms\Storage;


use Hyperf\Guzzle\ClientFactory;
use Upp\Traits\HelpTrait;

class SmsYun
{
    use HelpTrait;



    public function sendCodeChind($sms_tpl,$sms_pass,$phone,$code)
    {
        if(!$sms_tpl || !$sms_pass){
            return "400";
        }
        //模板ID：6026846
        $temp ='【Mcn商城】您的验证码是#code#。如非本人操作，请忽略本短信';
        $url  = 'https://sms.yunpian.com/v2/sms/tpl_single_send.json';
        try {
            $data = array(
                'tpl_id' => $sms_tpl,
                'tpl_value' => ('#code#').'='.urlencode((string)$code),
                'apikey' => $sms_pass,
                'mobile' => $phone,
            );

            $ch = curl_init();
            $timeout = 10;
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
            curl_setopt ($ch, CURLOPT_HEADER, false); //开启header  显示返回的Header区域内容
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
            curl_setopt ($ch, CURLOPT_HTTPHEADER, [
                'Accept:text/plain;charset=utf-8',
                'Content-Type:application/x-www-form-urlencoded','charset=utf-8'
            ]); //类型为json

            $file_contents = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($file_contents, true);

            if(empty($result) || !isset($result['code'])){
                return 400;
            }
            if($result['code'] != 0){
                return $result['code'];
            }
            return true;
        }catch (\Throwable $e){
            $error= ['file'=>'发送失败','msgs'=>$e->getMessage()];
            $this->logger('[发送短信]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return 400;
        }

    }

    public function sendCode($sms_tpl,$sms_pass,$phone,$code)
    {
        if(!$sms_tpl || !$sms_pass){
            return "400";
        }
        //模板ID：6026844
        $temp = '【Mcn】Your verification code is '.$code.'. If it is not your own operation, please ignore this message';
        $url  = "https://us.yunpian.com/v2/sms/single_send.json";
        try {
            $data = array(
                'tpl_id' => $sms_tpl,
                'apikey' => $sms_pass,
                'mobile' => $phone,
                'text'   => $temp
            );

            $ch = curl_init();
            $timeout = 10;
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_POST, true); // 发送一个常规的Post请求
            curl_setopt ($ch, CURLOPT_HEADER, false); //开启header  显示返回的Header区域内容
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
            curl_setopt ($ch, CURLOPT_HTTPHEADER, [
                'Accept:text/plain;charset=utf-8',
                'Content-Type:application/x-www-form-urlencoded','charset=utf-8'
            ]); //类型为json

            $file_contents = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($file_contents, true);

            if(empty($result) || !isset($result['code'])){
                return 400;
            }
            if($result['code'] != 0){
                return $result['code'];
            }
            return true;
        }catch (\Throwable $e){
            $error= ['file'=>'发送失败','msgs'=>$e->getMessage()];
            $this->logger('[发送短信]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return 400;
        }

    }

    public function getError($result)
    {
        $statusStr = array(
            '-4'=>'访问次数超限',
            '-5'=>'访问频率超限',
            '1'=>'请求参数缺失',
            '2'=>'请求参数格式错误',
            '3'=>'账户余额不足',
            '5'=>'未自动匹配到合适的模板',
            '7'=>'模板不可用',
            '10'=>'手机号防骚扰名单过滤',
            '14'=>'解码失败',
            '15'=>'签名不匹配',
            '16'=>'签名格式不正确',
            '18'=>'签名校验失败',
            '20'=>'暂不支持的国家地区',
            '23'=>'号码归属地不在模板可发送的地区内',
            '25'=>'手机号和内容个数不匹配',
            '53'=>'手机号接收超过频率限制（文本、语音、超级短信）',
            '56'=>'手机号码格式不正确',
        );

        return isset($statusStr[$result]) ? $statusStr[$result] : '未知错误';
    }
}