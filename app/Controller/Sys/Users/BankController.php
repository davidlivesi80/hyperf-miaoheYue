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
namespace App\Controller\Sys\Users;

use App\Common\Service\Users\UserBankService;
use Upp\Basic\BaseController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class BankController extends BaseController
{

    /**
     * @var UserBankService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserBankService $service)

    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['username','user_id']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {

        $data = $this->request->inputs(['bank_type','bank_account']);

        $this->validated($data, \App\Validation\Admin\BankValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fail('更新失败');
        }

        return $this->success('更新成功');

    }

    /**
     * 删除用户
     */
    public function remove($id){

        $this->service->remove($id);

        return $this->success('操作成功');
    }



}
