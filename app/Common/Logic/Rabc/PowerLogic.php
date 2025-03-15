<?php


namespace App\Common\Logic\Rabc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Rabc\AdminMenu;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;


class PowerLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return AdminMenu::class;
    }


    /**

     * 添加菜单

     */

    public  function createMenu($data)
    {
        if (!$this->create($data)) {
            return false;
        }
        //更新缓存
        $this->cachePutMenus();
        return true;

    }

    /**

     * 更新菜单

     */

    public  function updateMenu(int $id,$data)
    {
        if (!$this->update($id,$data)) {
            return false;
        }
        //更新缓存
        $this->cachePutMenus();
        return true;

    }

    /**

     * 删除菜单

     */
    public function removeMenu($id){

        if(!$this->remove($id)){
            return false;
        }
        //更新缓存
        $this->cachePutMenus();
        return true;
    }

    /**
     * 查询构造
    */
    public function search(array $where =[]){

        if(isset($where['title']) && $where['title'] !== ''){

            return $this->getQuery()->when($where, function ($query) use ($where){

                return $query->where('title', 'like', "%".$where['title']."%");

            })->orderBy('sort', 'asc')->get();

        }else{

            return $this->cacheableMenus();
        }

    }

    /**
     * @Cacheable(prefix="sys-menu", ttl=9000, listener="sys-menu-update")
     */
    public function cacheableMenus()
    {

        $menus =  $this->getQuery()->orderBy('sort', 'asc')->get()->toArray();

        $menuArrs = [];

        foreach ($menus as $key => $value){
            $menu['menuId'] = $value['id'];
            $menu['title'] = $value['title'];
            $menu['path'] = $value['path'];
            $menu['component'] = $value['component'];
            $menu['parentId'] = $value['parentId'];
            $menu['menuType'] =  $value['menuType'];
            $menu['openType'] =  $value['openType'];
            $menu['icon'] = $value['icon'];
            $menu['sort'] = $value['sort'];
            $menu['hide'] = $value['hide'];
            $menu['authority'] = $value['authority'];
            $menu['uid'] = null;
            $menuArrs[] = $menu;
        }

        return $menuArrs;

    }

    /**
     * @CachePut(prefix="sys-menu", ttl=9000)
     */
    public function cachePutMenus()
    {

        $menus =  $this->getQuery()->orderBy('sort', 'asc')->get()->toArray();

        $menuArrs = [];

        foreach ($menus as $key => $value){
            $menu['menuId'] = $value['id'];
            $menu['title'] = $value['title'];
            $menu['path'] = $value['path'];
            $menu['component'] = $value['component'];
            $menu['parentId'] = $value['parentId'];
            $menu['menuType'] =  $value['menuType'];
            $menu['openType'] =  $value['openType'];
            $menu['icon'] = $value['icon'];
            $menu['sort'] = $value['sort'];
            $menu['hide'] = $value['hide'];
            $menu['authority'] = $value['authority'];
            $menu['uid'] = null;
            $menuArrs[] = $menu;
        }

        return $menuArrs;

    }
    
    
}