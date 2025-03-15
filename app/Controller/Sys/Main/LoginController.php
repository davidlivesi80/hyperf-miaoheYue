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

namespace App\Controller\Sys\Main;

use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserWithdrawService;
use Upp\Basic\BaseController;
use \App\Common\Service\Rabc\UsersService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;



class LoginController extends BaseController
{

    /**
     * @var UsersService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UsersService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function index(){

        // 获取通过验证的数据...
        $this->validated($this->request->all(),\App\Validation\Admin\LoginValidation::class);

        $result = $this->service->doLogin($this->request->input('username'), $this->request->input('password'), $this->request->input('secret'));

        return $this->success('登录成功',$result['token']);

    }


    public function notify()
    {
         $data = $this->request->inputs(['id','hash','status']);
         //数据验证
         $this->validated($data,\App\Validation\Admin\WithdrawNotifyValidation::class);
         if($data['status'] == 2){
             //$result = $this->app(UserWithdrawService::class)->cancel($data['id'],$data['hash'],"钱包执行");
         }elseif($data['status'] == 1){
             $result = $this->app(UserWithdrawService::class)->confirm($data['id'],$data['hash'],'钱包执行');
         }else{
             return [ 'code' => 400, 'message' => '提现状态错误'];
         }
         if(!$result){
             return [ 'code' => 400, 'message' => '提现失败'];
         }
         return [ 'code' => 200, 'message' => '提现成功'];
    }

}