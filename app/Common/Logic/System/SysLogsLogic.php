<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysLogs;

class SysLogsLogic extends BaseLogic
{

    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysLogs::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('sys_logs.user_id', '=', 'user.id')->where('user.username', $where['username']);
            })->select('sys_logs.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['method']) && $where['method'] !== '', function ($query) use ($where){

            return $query->where('method', $where['method']);

        })->when(isset($where['ip']) && $where['ip'] !== '', function ($query) use ($where) {

            return $query->where('ip', 'like', "%".$where['ip']."%");

        })->when(isset($where['path']) && $where['path'] !== '', function ($query) use ($where) {

            return $query->where('path', 'like', "%".$where['path']."%");

        })->orderBy('id', 'desc');

        return $query;
    }
}