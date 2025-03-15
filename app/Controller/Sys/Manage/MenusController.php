<?php


namespace App\Controller\Sys\Manage;

use Upp\Basic\BaseController;
use App\Common\Service\Rabc\PowerService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class MenusController extends BaseController
{

    /**
     * @var PowerService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,PowerService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    /**
     * 所有菜单
     */
    public function lists()
    {
        $where = $this->request->inputs(['title']);

        $meuns  = $this->service->search($where);

        return $this->success('请求成功',$meuns);

    }

    /**
     * 添加菜单
     */
    public function create(){

        // 获取通过验证的数据...
        $this->validated($this->request->all(),\App\Validation\Admin\MenuCreateValidation::class);
        
        // 添加菜单
        $res = $this->service->create($this->request->all());
      
        if(!$res){
            $this->fail('添加失败');
        }

        return $this->success('添加成功'.$res);

    }

    /**
     * 更新菜单
     */
    public function update($id){
        
        $data = $this->request->inputs(['component','title','parentId','menuType','openType','hide','path','icon','sort','authority']);
        
        // 获取通过验证的数据...
        $this->validated($data,\App\Validation\Admin\MenuCreateValidation::class);
        
        // 添加菜单
        $res = $this->service->update($id,$data);

        if(!$res){
            $this->fail('更新失败');
        }

        return $this->success('更新成功');
    }

    /**
     * 删除菜单
     */
    public function remove($id){

        // 删除菜单
        $res = $this->service->remove($id);

        if(!$res){
            $this->fail('删除失败');
        }

        return $this->success('删除成功');
    }




}