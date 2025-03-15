<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysArticle;

class SysArticleLogic extends BaseLogic
{

    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysArticle::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['cate']) && $where['cate'] !== '', function ($query) use ($where){

            return $query->where('cate', $where['cate']);

        })->when(isset($where['lunb']) && $where['lunb'] > 0, function ($query) use ($where){

            return $query->where('lunb',$where['lunb']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where){

            return $query->where('status', $where['status']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('title', 'like', "%".$where['title']."%");

        })->when(isset($where['recommend']) && $where['recommend'] !== '', function ($query) use ($where) {

            return $query->where('recommend', $where['recommend']);

        })->when(isset($where['taned']) && $where['taned'] !== '', function ($query) use ($where) {

            return $query->where('taned', $where['taned']);

        })->when(isset($where['lang']) && $where['lang'] !== '', function ($query) use ($where) {

            return $query->where('lang', $where['lang']);

        })->orderBy("id", 'desc');


        return $query;
    }


}