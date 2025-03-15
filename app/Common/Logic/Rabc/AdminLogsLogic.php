<?php

namespace App\Common\Logic\Rabc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Rabc\AdminLog;

class AdminLogsLogic extends BaseLogic
{

    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return AdminLog::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('admin_user', function ($join) use($where){
                $join->on('admin_log manage_id', '=', 'admin_user.id')->where('admin_user.manage_name', $where['username']);
            });

        })->when(isset($where['method']) && $where['method'] !== '', function ($query) use ($where) {

            return $query->where('method', $where['method']);

        })->when(isset($where['ip']) && $where['ip'] !== '', function ($query) use ($where) {

            return $query->where('ip', 'like', "%" . $where['ip'] . "%");

        })->when(isset($where['path']) && $where['path'] !== '', function ($query) use ($where) {

            return $query->where('path', 'like', "%" . $where['path'] . "%");

        })->orderBy('id', 'desc');

        return $query;
    }
}