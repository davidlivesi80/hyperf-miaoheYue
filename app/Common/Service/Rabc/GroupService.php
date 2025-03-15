<?php


namespace App\Common\Service\Rabc;

use Upp\Basic\BaseService;
use App\Common\Logic\Rabc\GroupLogic;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;

class GroupService extends BaseService
{

    /**
     * @var GroupLogic
     */
    public function __construct(GroupLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 检查分组名称是否存在

     */

    public function checkName($code,$id = null)
    {

        $res = $this->logic->fieldExists('group_code', $code,$id);

        if($res){
            throw new AppException('该用户组已存在',400);
        }

    }


    /**

     * 删除菜单

     */
    public function remove($groupId){

        Db::beginTransaction();

        try {

            $this->logic->remove($groupId);

            $this->app(UserGroupService::class)->getQuery()->where('group_id',$groupId)->delete();

            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();


            throw new AppException($e->getMessage(),400);
        }
    }

    /**

     * 批量删除用户

     */
    public function batch($groupIds){

        Db::beginTransaction();

        try {

            $this->logic->batch($groupIds);

            $this->app(UserGroupService::class)->getQuery()->whereIn('group_id',$groupIds)->delete();

            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage(),400);
        }

    }

    /**

     * 授权菜单

     */
    public function give($id,$ids){
        
        if($ids){
            $authIds = implode(',',$ids);
        }else{
            $authIds = '';
        }

        $data['authIds'] = $authIds;

        
        return $this->logic->update($id,$data);


    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;

    }


}