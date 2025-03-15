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
namespace App\Controller\Sys\System;

use Upp\Basic\BaseController;
use App\Common\Service\System\SysContractService;
use App\Common\Service\Users\MemberService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Phper666\JWTAuth\JWT;

class ContractController extends BaseController
{

    /**
     * @var CardService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysContractService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {

        return [];

        $where = $this->request->inputs(['title','id']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where);

        return $this->success('请求成功',$lists);


    }
    
    /**
     * 添加
     */
    public function create()
    {

        $this->validated($this->request->all(), \App\Validation\Admin\ContractValidation::class);
        
        $this->service->checkName($this->request->input('contract_name'));

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            return $this->fails('添加失败');
        }

        return $this->success('添加成功');

    }

    /**
     * 更新
     */

    public function update($id)
    {
        return $this->fails('更新失败');

        $data = $this->request->inputs(['contract_title','contract_name','contract_address','contract_abi']);

        $this->validated($data, \App\Validation\Admin\ContractValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fails('更新失败');
        }

        return $this->success('更新成功');
    }
    
    /**
     * 删除
     */
    public function remove($id){
        return $this->fails('更新失败');
        $res = $this->service->remove($id);
        
        if(!$res){
            return $this->fails('操作失败');
        }

        return $this->success('操作成功');
    }

}
