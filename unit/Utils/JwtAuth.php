<?php

namespace Upp\Utils;

use Upp\Exceptions\AppException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Firebase\JWT\JWT;

use Upp\Traits\HelpTrait;


class JwtAuth
{

    use HelpTrait;


    protected $token;

    public function getToken($key, $name,$type)
    {
        $uri = $this->app(RequestInterface::class)->getUri();
        $host =  $uri->getScheme() . '://' . $uri->getHost();
        $time = time();
        if($type=='sys'){
            $exp_time = strtotime(env('jwt.admin_exp_time', '+365 day'));
        }else{
            $exp_time = strtotime(env('jwt.exp_time', '+365 day'));
        }
        $exp_time = strtotime(date('Y-m-d 23:59:59', $exp_time));
        $params  = [
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => $exp_time,
        ];
        $params['jti'] = compact('key','name', 'type');
        $token = JWT::encode($params, env('app.app_key', 'ErrorJob2014_99u#sa'));
        return compact('token', 'params');
    }

    public function parseToken($token)
    {
        try{
            $this->token = $token;
            $info = explode('.',$this->token);
            if(count($info)==3){
                list($headb64, $bodyb64, $cryptob64)=explode('.',$this->token);
                $playLoad=JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
                return [$playLoad->jti->key,$playLoad->jti->name,$playLoad->jti->type];
            }
            $this->token = null;
            return [0,'',''];
        }catch (\Throwable $e){
            throw new AppException('token_01',401);//token格式错误
        }

    }


    /**
     * 验证token
     */
    public function verifyToken()
    {
        JWT::$leeway = 60;
        JWT::decode($this->token, env('app.app_key', 'ErrorJob2014_99u#sa'), array('HS256'));
        $this->token = null;
    }


    /**
     * 获取token并放入令牌桶
     * @param   $key
     * @param string $type
     * @param array $params
     * @return array
     */
    public function createToken($key, $name, $type)
    {
        $tokenInfo = $this->getToken($key, $name , $type);
        $md5Token = md5($tokenInfo['token']);
        $exp = $tokenInfo['params']['exp'] - $tokenInfo['params']['iat'] + 60;
        if($type == 'sys'){
            $info = ['id' => $key, 'type' => $type,'name'=>$name, 'token' => $tokenInfo['token'], 'exp' => $exp];
            $this->getCache()->set('sysjwt:'.$md5Token,$info,(int)$exp);
        }else{
            $info = ['id' => $key, 'type' => $type,'name'=>$name,'token' => $tokenInfo['token'], 'exp' => $exp];
            $this->getCache()->set('apijwt:'.$md5Token,$info,(int)$exp);
        }
        if(!$info){
            throw new AppException('缓存token失败',400);
        }
        return $tokenInfo;
    }

    /**
     * 获取token并放入令牌桶
     * @param   $key
     * @param string $type
     * @param array $params
     * @return array
     */
    public function continueToken($key,$cacheToken)
    {

        return '';
    }

}
