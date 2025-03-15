<?php

declare(strict_types=1);

namespace Upp\Service;

use Upp\Traits\HelpTrait;



class PaymentService
{

    use HelpTrait;

    protected $notifyUrl = '';

    function __construct($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * ASCII码从小到大排序 签名不带key 返回大写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_key($param, $key)
    {
        $str = "";
        ksort($param);
        foreach ($param as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $post_data = substr($str, 0, -1) . $key;
        return strtoupper(md5($post_data));
    }


    /**
     * ASCII码从小到大排序 签名带key 返回大写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_key_per($param, $key)
    {
        $str = "";
        ksort($param);
        foreach ($param as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $post_data = substr($str, 0, -1) . "&key=".$key;
        return strtoupper(md5($post_data));
    }


    /**
     * ASCII码从小到大排序 签名带access_token 返回大写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_access_token_per($params, $accessToken)
    {
        // 按文档参数顺序排序
        $order = ['userName', 'amount', 'outOrderId', 'returnUrl', 'frontReturnUrl'];
        $sortedParams = [];

        foreach ($order as $key) {
            if (isset($params[$key]) && $params[$key] !== '') {
                $sortedParams[] = $key . '=' . $params[$key];
            }
        }

        // 拼接 access_token
        $signString = implode('&', $sortedParams) . '&access_token=' . $accessToken;

        // 生成 MD5 签名并转换为大写
        return strtoupper(md5($signString));
    }

    function paramArraySign($paramArray, $mchKey){
        ksort($paramArray);  //字典排序
        reset($paramArray);
        $md5str = "";
        foreach ($paramArray as $key => $val) {
            if( strlen($key)  && strlen($val) ){
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $mchKey));  //签名
        return $sign;
    }


    /**
     * ASCII码从小到大排序 签名不带key 返回小写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_key_x($param, $key)
    {
        $str = "";
        ksort($param);
        foreach ($param as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $post_data = substr($str, 0, -1) . $key;
        return md5($post_data);
    }

    /**
     * ASCII码从小到大排序 签名带key 返回小写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_belt_key($param, $key)
    {
        $str = "";
        ksort($param);
        foreach ($param as $k => $v) {
            if ($v != ""){
                $str .= $k . "=" . $v . "&";
            }
        }
        $post_data = substr($str, 0, -1) . '&key=' . $key;
        return md5($post_data);
    }

    /**
     * ASCII码从小到大排序 签名带key 返回小写md5
     * @param $param
     * @param $key
     * @return string
     */
    public function sign_belt_key_Sign($param, $key)
    {
        $str = "";
        ksort($param);
        foreach ($param as $k => $v) {
            if ($v != ""){
                $str .= $k . "=" . $v . "&";
            }
        }
        $post_data = substr($str, 0, -1) . '&keySign=' . $key."Apm";
        return md5($post_data);
    }

    /**
     * 支付统一返回格式
     */
    public function callbcakReturn($code = "", $msg = "", $backUrl = "")
    {
        return array(
            'code' => $code,
            'msg' => $msg,
            'backurl' => $backUrl
        );
    }




}