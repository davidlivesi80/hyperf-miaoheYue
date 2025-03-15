<?php
declare(strict_types=1);

namespace Upp\Service;

use Upp\Traits\HelpTrait;
use Upp\Exceptions\AppException;

class YoudaoService
{
    use HelpTrait;
    private static $URL =  "https://openapi.youdao.com/api";
    private static $APP_KEY = '7d38a83272bfdda3'; // 替换为您的应用ID
    private static $APP_SECRET = 'iB5HYOR2FsgwFVYXiqgBtCXbmruLzCJ8'; // 替换为您的密钥
    /*****************************************
     * 单次查询最大字符数	每小时最大查询次数	每小时最大查询字符数	支持语言
     * 5000	                  100万	      120万	            详见语种表
     *****************************************/

    public function do_call($q="", $from="zh",$to="en"){
        // 简体中文 zh-CHS、繁体中文 zh-CHT、 英语 en 、俄语	ru 、  日语 ja 、韩语 ko 、 泰语 th、 越南语 vi、
        // 高棉语 km、  印度尼西亚语 id 、 马来语 ms 、 葡萄牙语 pt 、 哈萨克语 kk 、
        $sysLang = [
            "zh"            =>  "zh-CHS",//简体中文
            "zhHant"        =>  "zh-CHT",//繁体中文
            "en"            =>  "en",    //英语
            "eyu"           =>  "ru",    //俄语
            "riyu"          =>  "ja",    //日语
            "ko"            =>  "hanyu", //韩语
            "th"            =>  "taiyu", //泰语
            "vi"            =>  "yuenan",//越南语
            "jianpuzhaiyu"  =>  "km",    //高棉语
            "yindu"         =>  "id",    //印度尼西亚语
            "malaixiyayu"   =>  "ms",    //马来语
            "baxiyu"        =>  "pt",    //葡萄牙语
            "hasakesitanyu" =>  "kk",    //哈萨克语
        ];
        if(empty($q)){return "";}
        $from = isset($sysLang[$from]) ? $sysLang[$from] : "zh-CHS";
        $to = isset($sysLang[$to]) ? $sysLang[$to] : "en";
        $params = ['q' => $q, 'from' => $from, 'to' => $to];
        $paramsSign = $this->add_auth_params($params, self::$APP_KEY, self::$APP_SECRET);
        $res = $this->GuzzleHttpGet( self::$URL, $paramsSign);
        if(!$res){
            //写入错误日志
            $error = ['msgs'=>"网络请求错误！！！"];
            throw new AppException(json_encode($error,JSON_UNESCAPED_UNICODE),400);
        }
        $res = json_decode($res,true);
        if($res['errorCode'] != 0){
            //写入错误日志
            $error = ['code'=>$res['errorCode'],'msgs'=>"翻译错误"];
            throw new AppException(json_encode($error,JSON_UNESCAPED_UNICODE),400);
        }
        if(!isset($res['translation'])){
            $error = ['code'=>$res['errorCode'],'msgs'=>"翻译五结果"];
            throw new AppException(json_encode($error,JSON_UNESCAPED_UNICODE),400);
        }
        return $res['translation'];//返回数组
    }

    public function add_auth_params($param, $appKey, $appSecret)
    {
        if (array_key_exists('q', $param)) {
            $q = $param['q'];
        } else {
            $q = $param['img'];
        }
        $salt = $this->create_uuid();
        $curtime = strtotime("now");
        $sign = $this->calculate_sign($appKey, $appSecret, $q, $salt, $curtime);
        $param['appKey'] = $appKey;
        $param['salt'] = $salt;
        $param["curtime"] = $curtime;
        $param['signType'] = 'v3';
        $param['sign'] = $sign;
        return $param;
    }

    public function create_uuid()
    {
        $str = md5(uniqid((string)mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $uuid;
    }

    public function calculate_sign($appKey, $appSecret, $q, $salt, $curtime)
    {
        $strSrc = $appKey . $this->get_input($q) . $salt . $curtime . $appSecret;
        return hash("sha256", $strSrc);
    }

    public function get_input($q)
    {
        if (empty($q)) {
            return null;
        }
        $len = mb_strlen($q, 'utf-8');
        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }
}