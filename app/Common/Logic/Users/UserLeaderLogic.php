<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserLeader;

class UserLeaderLogic extends BaseLogic
{
    /**
     * @var UserLeader
     */
    protected function getModel(): string
    {
        return UserLeader::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=',$where['timeStart']);

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        });
        return $query;

    }


}