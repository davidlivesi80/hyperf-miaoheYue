<?php

namespace App\Common\Service\System;

use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\System\SysArticleLogic;

class SysArticleService extends BaseService
{

    /**
     * @var SysArticleLogic
     */
    public function __construct(SysArticleLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllCate()
    {
        return [
            ['id'=>1,'title'=>'帮助中心'],

            ['id'=>2,'title'=>'公告中心'],

            ['id'=>4,'title'=>'用户协议'],

            ['id'=>5,'title'=>'关于我们'],

            ['id'=>6,'title'=>'隐私协议'],
            
            ['id'=>7,'title'=>'第一轮播'],
            
            ['id'=>8,'title'=>'平台规则'],
            
            ['id'=>9,'title'=>'第三轮播'],
            
            ['id'=>10,'title'=>'第四轮播'],
        ];
    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get()->toArray();

        return $list;
    }

    /**
     * 弹窗列表
     */
    public function tanls(){

        $list = $this->logic->search(['cate'=>2,'recommend'=>1])->get()->toArray();

        return $list;
    }

    /**
     * 弹窗列表
     */
     public function taned($id = 0,$lang="zh"){

        Db::beginTransaction();

        try {

            $this->logic->getQuery()->where('taned',1)->where('lang',$lang)->update(['taned'=>0]);

            $this->logic->update($id,['taned'=>1]);

            Db::commit();

        } catch(\Throwable $e){

            Db::rollBack();

            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[文章操作]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


}