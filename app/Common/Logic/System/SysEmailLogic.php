<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysEmail;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;
class SysEmailLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysEmail::class;
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

    /**
     * @Cacheable(prefix="sys-email", ttl=9000, listener="sys-email-update")
     */
    public function cacheableEmail()
    {

        $lists =  $this->getQuery()->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-email", ttl=9000)
     */
    public function cachePutEmail()
    {

        $lists =   $this->getQuery()->get()->toArray();


        return $lists;

    }
}