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

use Upp\Basic\BaseController;
use App\Common\Service\Users\UserMessageService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class MessageController extends BaseController
{

    /**
     * @var UserMessageService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,UserMessageService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['username']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


    /**
     * 权限管理|用户管理@添加用户
     */

    public function reply($id)
    {

        $reply = $this->request->post('reply');
        if(!$reply){
        	return $this->fail('回复内容不能为空');
        }
        // 更新
        $res = $this->service->update($id,['reply'=>$this->request->post('reply'),'is_reply'=>1]);

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

    /**
     * 批量删除
     */
    public function batch(){

        $this->service->batch($this->request->input('ids'));

        return $this->success('操作成功');
    }




}
