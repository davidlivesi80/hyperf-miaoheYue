<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysCoinsLogic;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;

class SysCoinsService extends BaseService
{
    /**
     * @var SysCoinsLogic
     */
    public function __construct(SysCoinsLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 添加菜单

     */

    public function create($data)
    {
        $menu = $this->logic->fieldExists('coin_symbol',strtolower($data['coin_symbol']));

        if ($menu) {

            throw new AppException('资产已存在,请重新填写',400);

        }

        Db::beginTransaction();

        try {
            $data['net_id'] = $data['net_id'];
            $data['coin_type'] = $data['coin_type'];
            $data['coin_name'] = strtoupper($data['coin_name']);
        	$data['coin_symbol'] = strtolower($data['coin_symbol']);

            Db::table('sys_coins')->insert($data);

            Db::statement("ALTER TABLE `user_balance` ADD `".strtolower($data['coin_symbol'])."` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT '0';");

            Db::commit();

        } catch(\Throwable $e){

            Db::rollBack();

            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[币种操作]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }


    }

    /**

     * 删除用户

     */
    public function remove($id){

        $entity = $this->logic->find($id);

        if (!$entity) {
            throw new AppException('资产不存在',400);
        }

        Db::beginTransaction();
        try {

            Db::table('sys_coins')->where('id',$entity->id)->delete();

            Db::statement("ALTER TABLE `user_balance` DROP COLUMN `".$entity->coin_symbol."`;");

            Db::commit();

        } catch(\Throwable $e){

            Db::rollBack();

            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[币种操作]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));;
            return false;
        }

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
    public function column(array $where, $field='id', $keys = 'id'){

        $list = $this->logic->getQuery()->where($where)->pluck($field,$keys);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function columns(array $where,$field=[]){

        $list = $this->logic->search($where)->select($field)->get();

        return $list;
    }

    /**
     * 获取缓存
     */
    public function value($symbol = 'usdt'){
        $symbol = $symbol ?? 'usdt';
        $list = $this->cachePutCoins();
        $keys = array_column($list,'coin_symbol');
        $index = array_search($symbol,$keys);
        return isset($list[$index]) ? $list[$index] : '';
    }


}