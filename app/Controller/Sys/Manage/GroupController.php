<?php


namespace App\Controller\Sys\Manage;

use Upp\Basic\BaseController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Common\Service\Rabc\GroupService;
use App\Common\Service\Rabc\PowerService;

class GroupController extends BaseController
{
    /**
     * @var GroupService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,GroupService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }
    /**
     * 所有角色
     */
    public function lists()
    {
        $where = $this->request->inputs(['group_name','group_code']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    /**
     * 添加角色
     */
    public function create(){

        $this->service->checkName($this->request->input('group_code'));

        // 获取通过验证的数据...
        $this->validated($this->request->all(),\App\Validation\Admin\GroupValidation::class);

        // 添加
        $res = $this->service->create($this->request->all());

        if(!$res){
            $this->fail('添加失败');
        }

        return $this->success('添加成功');

    }

    /**
     * 更新角色
     */
    public function update($id){

        $this->service->checkName($this->request->input('group_code'),$this->request->input('id'));

        $data = $this->request->inputs(['group_name','group_code']);
        // 获取通过验证的数据...
        $this->validated($data,\App\Validation\Admin\GroupValidation::class);

        // 更新
        $res = $this->service->update($id,$data);

        if(!$res){
            $this->fail('更新失败');
        }

        return $this->success('更新成功');
    }

    /**
     * 删除角色
     */
    public function remove($id){

        // 删除
        $res = $this->service->remove($id);

        if($res !== true){
            $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    /**
     * 批量删除
     */
    public function batch(){

        $res = $this->service->batch($this->request->input('ids'));

        if($res !== true){
            $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    /**
     * 权限列表
     */
    public function auth($id){

        $entity = $this->service->find($id);

        if($entity->authIds){
            $authIds = explode(',',$entity->authIds);
        }else{
            $authIds = [];
        }

        $auth = $this->app(PowerService::class)->search();

        foreach ($auth as $key => $value){
            if( in_array($value['menuId'],$authIds)){
                $auth[$key]['checked'] = true;
            }else{
                $auth[$key]['checked'] = false;
            }
        }

        return $this->success('权限列表',$auth);
    }

    /**
     * 分配权限
     */
    public function give($id){
        
       
        $res = $this->service->give($id,$this->request->input('ids'));

        if(!$res){
            $this->fail('分配失败');
        }

        return $this->success('分配成功');
    }

}