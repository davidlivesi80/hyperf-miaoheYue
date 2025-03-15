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
namespace App\Controller\Sys\Game;

use Upp\Basic\BaseController;
use App\Common\Service\System\SysGameService;
use App\Common\Service\Users\UserGameService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class GameController extends BaseController
{

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysGameService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {
        $where = $this->request->inputs(['title']);

        $lists  = $this->service->search($where);

        return $this->success('请求成功',$lists);
    }


    /**
     * 权限管理|用户管理@添加用户
     */

    public function create()
    {
        $this->validated($this->request->all(), \App\Validation\Admin\SportMatchValidation::class);

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            return $this->fail('添加失败');
        }
        //清除缓存
        $this->service->cachePutMenus();

        return $this->success('添加成功');

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {
        
        $data = $this->request->inputs(['name','title','image','url','detail','sort']);
         
        $this->validated($data, \App\Validation\Admin\SportMatchValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            return $this->fail('更新失败');
        }
        //清除缓存
        $this->service->cachePutMenus();

        return $this->success('更新成功');

    }

    /**
     * 删除用户
     */
    public function remove($id){

        $res = $this->service->remove($id);

        if(!$res){
            return $this->fail('操作失败');
        }
        //清除缓存
        $this->service->cachePutMenus();

        return $this->success('操作成功');
    }


    public function status ($id)
    {
        $res = $this->service->updateField($id,'status',$this->request->input('status'));

        if(!$res){
            return $this->fail('操作失败');
        }

        //清除缓存
        $this->service->cachePutMenus();

        return $this->success('操作成功');
    }

    /**
     * 游戏记录
     */
    public function order()
    {

        $where = $this->request->inputs(['username','game_id','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserGameService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


}
