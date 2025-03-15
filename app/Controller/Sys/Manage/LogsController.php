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
namespace App\Controller\Sys\Manage;

use Upp\Basic\BaseController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Common\Service\Rabc\AdminLogsService;

class LogsController extends BaseController
{


    /**
     * @var AdminLogsService
     */
    private  $logger;

    public function __construct(RequestInterface $request, ResponseInterface $response,ValidatorFactoryInterface $validator, AdminLogsService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    public function lists()
    {
        $where = $this->request->inputs(['username','method','ip','path']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 批量删除
     */
    public function batch(){

        return $this->fail('操作失败');

        $res = $this->service->batch($this->request->input('ids'));

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    /**
     * 清空
     */

    public function clear()
    {

        return $this->fail('操作失败');

        $res = $this->service->clear();

        return $this->success('请求成功');
    }



}
