<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysVersion;

class SysVersionLogic extends BaseLogic
{
    /**
     * @var SysVersion
     */
    protected function getModel(): string
    {
        return SysVersion::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where){

            return $query->where('type', $where['type']);

        })->orderBy('id', 'desc');

        return $query;
    }


}