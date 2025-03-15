<?php


namespace App\Common\Logic\Rabc;


use Upp\Basic\BaseLogic;
use App\Common\Model\Rabc\AdminUser;


class UsersLogic extends BaseLogic
{

    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return AdminUser::class;
    }



    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['manage_name']) && $where['manage_name'] !== '', function ($query) use($where){

            return $query->where('manage_name', 'like', "%".$where['manage_name']."%");

        })->orderBy('id', 'desc');

        return $query;

    }

}