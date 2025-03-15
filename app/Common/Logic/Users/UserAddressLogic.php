<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserAddress;

class UserAddressLogic extends BaseLogic
{
    /**
     * @var UserAddress
     */
    protected function getModel(): string
    {
        return UserAddress::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){


        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_address.user_id', '=', 'user.id')->where('user.username', $where['username']);
            })->select('user_address.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        });




        return $query->orderBy('id', 'desc');

    }

}