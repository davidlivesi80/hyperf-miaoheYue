<?php


namespace App\Common\Service\Otc;


use Upp\Basic\BaseService;
use App\Common\Logic\Otc\OtcCoinsLogic;

class OtcCoinsService extends BaseService
{
    /**
     * @var OtcCoinsLogic
     */
    public function __construct(OtcCoinsLogic $logic)
    {
        $this->logic = $logic;
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
    public function searchAPi(array $where){

        $list = $this->logic->search($where)->get();

        return $list;
    }

}