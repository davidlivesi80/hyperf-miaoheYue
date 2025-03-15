<?php


namespace Upp\Service;

use App\Common\Service\Users\UserService;
use Upp\Exceptions\AppException;

use Upp\Traits\HelpTrait;
use Upp\Utils\JwtAuth;

class ParseToken
{
    use HelpTrait;


    //创建token
    public function toToken($key,$name,$type="api"){
        $token = $this->app(JwtAuth::class)->createToken($key,$name,$type);
        //单点登录
        $module = $type == 'sys' ? 'sysjwt' : 'apijwt';
        $this->getCache()->set($module.':once:'.$key,$token);
        return $token;
    }

    //解析token
    public function doToken($token,$type='api')
    {
        if (!$token || $token === 'undefined') {
            throw new AppException('token_03',401);//token不能为空
        }

        $module = $type == 'sys' ? 'sysjwt' : 'apijwt';
        /** @var  $jwt JwtAuth*/
        $jwt = $this->app(JwtAuth::class);
        [$key,$name,$type] = $jwt->parseToken($token);
        $md5Token = md5($token);
        $user = $this->app(UserService::class)->find($key);
        if( $module == "apijwt" && $user->enable == 0 ){
            $this->getCache()->delete($module.':'.$md5Token);
        }
        $cacheToken = $this->getCache()->get($module.':'.$md5Token);
        if (!$cacheToken) {
            throw new AppException('token_05',401);//登录过期，请从新登录
        }else{
            //单点登录
            $oldtoken = $this->getCache()->get($module.':once:'.$key);
            if($token != $oldtoken['token']){
                throw new AppException("token_04",401);//您的账号在他方登录，请重新登录
            }
            if($cacheToken['type']!=$type){
                throw new AppException("token_05",401);
            }
        }
        //自动续签
        $this->getCache()->set($module.':'.$md5Token,$cacheToken,86400 * 365);

        return [$key,$name,$type];
    }


    //清除token
    public function reToken($key,$token,$type='api'){
        if (!$token || $token === 'undefined') {
            return $this->json( "token为空",401);
        }
        $module = $type == 'sys' ? 'sysjwt' : 'apijwt';
        $md5Token = md5($token);
        $this->getCache()->delete($module.':'.$md5Token);
        $this->getCache()->delete($module.':once:'.$key);
        return true;
    }

}