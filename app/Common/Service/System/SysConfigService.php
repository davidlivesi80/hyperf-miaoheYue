<?php
namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysConfigLogic;
use Upp\Exceptions\AppException;

class SysConfigService extends BaseService
{
    /**
     * @var SysConfigLogic
     */
    public function __construct(SysConfigLogic $logic)
    {
        $this->logic = $logic;
    }


    /**

     * 检查key

     */
    public function checkKeys($key)
    {

        return $this->logic->fieldExists('key',$key);

        if($res){
            throw new AppException('配置项已存在',400);
        }

    }

    public function getAllType()
    {

        return [

            ['id'=>1,'title'=>'站点配置'],

            ['id'=>2,'title'=>'通讯配置'],

            ['id'=>3,'title'=>'参数配置'],

            ['id'=>4,'title'=>'其他配置'],

            ['id'=>5,'title'=>'支付配置'],

            ['id'=>6,'title'=>'储存配置'],

        ];

    }

    public function getAllEles()
    {

        return [
            ['id'=>1,'name'=>'input','title'=>'输入框'],

            ['id'=>2,'name'=>'textarea','title'=>'文本框'],

            ['id'=>3,'name'=>'radio','title'=>'单选框'],

            ['id'=>4,'name'=>'check','title'=>'多选框'],

            ['id'=>5,'name'=>'upload','title'=>'上传图'],

            ['id'=>6,'name'=>'upload','title'=>'时间轴']

        ];

    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10, $order = 'id' , $sort = 'asc'){

        $list = $this->logic->search($where,$order,$sort)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 获取配置值
    */
    public function value($keys = ''){
        if(!$keys){return ''; }
        $list = $this->cacheableConfig();
        return isset($list[$keys]) ? $list[$keys] : '';
    }


}