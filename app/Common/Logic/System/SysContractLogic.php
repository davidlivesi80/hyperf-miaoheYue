<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysContract;

class SysContractLogic extends BaseLogic
{
    /**
     * @var SysContract
     */
    protected function getModel(): string
    {
        return SysContract::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('contract_title', 'like', "%".$where['title']."%");

        })->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {

            return $query->where('id', $where['id']);

        })->orderBy('id', 'desc');

        return $query;
    }


}