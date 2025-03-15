<?php

namespace App\Common\Service\Users;

use Carbon\Carbon;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSecondKolLogic;

class UserSecondKolService extends BaseService
{
    /**
     * @var UserSecondKolLogic
     */
    public function __construct(UserSecondKolLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with(['user' => function ($query) {

            return $query->select('username', 'id');

        }])->paginate($perPage, ['*'], 'page', $page);

        $lastNow = Carbon::now();$startWeek = $lastNow->startOfWeek()->subWeek()->format('Y-m-d H:i:s'); $endWeek = $lastNow->endOfWeek()->format('Y-m-d H:i:s');
        $list->each(function ($item) use($startWeek,$endWeek){
            $lastweek = $this->logic->getQuery()->where('user_id',$item['user_id'])
                ->where('created_at','>=',$startWeek)->where('created_at','<=',$endWeek)->first();
            $item['lastweek'] = $lastweek->reward;
            return $item;
        });

        return $list;

    }


    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }


}