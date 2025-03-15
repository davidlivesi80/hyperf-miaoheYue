<?php


namespace App\Common\Service\Rabc;

use Upp\Basic\BaseService;
use App\Common\Logic\Rabc\PowerLogic;
use Upp\Exceptions\AppException;

class PowerService extends BaseService
{

    /**
     * @var PowerLogic
     */
    public function __construct(PowerLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 添加菜单

     */

    public  function create($data)
    {
        if(isset($data['path']) && $data['path'] !=''){

            $menu = $this->logic->fieldExists('path',$data['path']);

            if ($menu) {

                throw new AppException('菜单已存在,请重新填写',400);

            }
        }

        return $this->logic->createMenu($data);

    }

    /**

     * 更新菜单

     */

    public  function update(int $id,$data)
    {
        return $this->logic->updateMenu($id,$data);

    }

    /**

     * 删除菜单

     */
    public function remove($id){

        $childs = $this->logic->whereExists(['parentId'=>$id]);

        if($childs){
            throw new AppException('该菜单存在子菜单，不能直接删除',400);
        }

        return $this->logic->removeMenu($id);

    }


    /**
     * 获取左侧菜单
     */
    public function getLeftMenus($manageId)
    {

        $menus = $this->logic->cacheableMenus();

        $leftMenus = [];

        $menusAuthIds = $this->app(UsersService::class)->getMenus($manageId);

        foreach ($menus as $key => $value){
            if($value['menuType'] == 0){
                if(in_array($value['menuId'],$menusAuthIds)){
                    $leftMenus[] = $value;
                }
            }
        }

        return $leftMenus; //ListToTree($leftMenus,0,'menuId','parentId');
    }


    
}