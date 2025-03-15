<?php


namespace App\Common\Logic\Rabc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Rabc\AdminGroup;

class GroupLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return AdminGroup::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when($where['group_name'] && $where['group_name'] !=='', function ($query) use($where){

            return $query->where('group_name', 'like', "%".$where['group_name']."%");

        })->when($where['group_code'] && $where['group_code'] !=='', function ($query) use($where){

            return $query->where('group_code', 'like', "%".$where['group_code']."%");

        })->orderBy('id', 'desc');

        return $query;

    }


}