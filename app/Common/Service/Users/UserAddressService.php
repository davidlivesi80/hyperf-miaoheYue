<?php


namespace App\Common\Service\Users;

use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserAddressLogic;
use Hyperf\DbConnection\Db;
use Upp\Exceptions\AppException;

class UserAddressService extends BaseService
{
    /**
     * @var UserAddressLogic
     */
    public function __construct(UserAddressLogic $logic)
    {
        $this->logic = $logic;
    }

    public function check($userId,$id){

        $info = $this->logic->getQuery()->where('user_id',$userId)->where('id',$id)->first();

        if (!$info) {
            throw new AppException('该地址不存在',400);
        }

        return $info;
    }

    /**

     * 添加

     */
    public function create($userId,$data){

        $count = $this->logic->getQuery()->where('user_id',$userId)->count();

        if ($count >= 5) {
            throw new AppException('收获地址最多5个',400);
        }

        Db::beginTransaction();

        try {
            //清除默认
            $ids = $this->logic->getQuery()->where('user_id',$userId)->pluck('id')->toArray();

            $this->logic->updates($ids,['is_default'=>0]);
            //新增地址
            $data['user_id']   =  $userId;

            $this->logic->create($data);

            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage(),400);
        }

    }

    /**

     * 修改

     */
    public function update($userId,$id,$data){

        $this->check($userId,$id);

        Db::beginTransaction();

        try {
            //清除默认
            $ids = $this->logic->getQuery()->where('user_id',$userId)->where('id','<>',$id)->pluck('id')->toArray();

            $this->logic->updates($ids,['is_default'=>0]);
            //修改地址
            $data['is_default']   =  1;

            $this->logic->update($id,$data);

            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage(),400);
        }
        
    }

    /**

     * 修改

     */
    public function remove($userId,$id){

        $this->check($userId,$id);

        return $this->logic->remove($id);

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('username','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;

    }

}