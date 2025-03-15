<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysDataLead;

class SysDataLeadLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysDataLead::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where) {

            return $query->where('username', 'like', "%".$where['username']."%");

        })->orderBy('id', 'desc');

        return $query;
    }


}