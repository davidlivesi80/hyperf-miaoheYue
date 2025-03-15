<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller\Api\Lottery;


use Upp\Basic\BaseController;
use App\Common\Service\System\SysLotteryListService;
use App\Common\Service\System\SysLotteryService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserLotteryService;
use App\Common\Service\Users\UserLotteryIncomeService;
use App\Common\Service\Users\UserRewardService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\EmsService;
use Upp\Traits\HelpTrait;


class LotteryController extends BaseController
{
    use HelpTrait;

    /**
     * @var UserLotteryService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, UserLotteryService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    public function info(){
        $id  = $this->request->input('id',1);
        $result = $this->app(SysLotteryService::class)->searchApi(['id'=>$id]);
        return $this->success('success',$result);
    }

    /**
     * 竞猜
     */
    public function create()
    {
        $userId = $this->request->query('userId');
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_robot_create_' . $userId,5)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['lottery_id','paysword','lottery_num','lottery_bei','lottery_wei','lottery_bit','lottery_type']);
        $this->validated($data, \App\Validation\Api\LotteryOrderValidation::class);
        if(0 >= intval($data['lottery_bei'])){
            $data['lottery_bei'] = 1;
        }

        //$this->app(UserService::class)->checkPaysOk($this->request->query('userId'),$data['paysword']);
        // 添加
        $res = $this->service->create($this->request->query('userId'),$data['lottery_id'],$data['lottery_wei'],$data['lottery_bit'],$data['lottery_type'],$data['lottery_num'], intval($data['lottery_bei']));
        if(!$res){
            return $this->fail('fail');
        }
        return $this->success('success',$res);
    }


    /*竞猜记录*/
    public function order()
    {
        $where['user_id'] = $this->request->query('userId');

        $where['ucard_id'] = $this->request->query('ucard_id');

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where, [], $page ,$perPage);

        return $this->success('success',$lists);
    }

    /**
     * 期号记录
     */
    public function logs()
    {

        $sellet_sn = $this->app(UserLotteryService::class)->checkSettleTime();
        $where['lottery_id'] = 1;
        $where['ids'] = $this->app(SysLotteryListService::class)->getQuery()->where('lottery_id',1)->where('sn',$sellet_sn)->first()->id;
        $perPage = $this->request->input('limit');
        $page = $this->request->input('page');
        $lists = $this->app(SysLotteryListService::class)->search($where,$page,$perPage);
        $lists->each(function ($item){
            $item['price'] = intval($item['price']);
            $item['sn_time'] = strtotime($item['sn'] . "00");
            return $item;
        });
        return $this->success('success',$lists);

    }





}
