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
namespace App\Controller\Api\Second;


use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserSecondIncomeService;
use App\Common\Service\Users\UserSecondQuickenService;
use Carbon\Carbon;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\System\SysSecondService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Service\EmsService;
use Upp\Traits\HelpTrait;


class SecondController extends BaseController
{
    use HelpTrait;

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request, ResponseInterface $response, ValidatorFactoryInterface $validator, UserSecondService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function tops(){
        $result = $this->app(SysSecondService::class)->searchExp(['tops'=>1,'status'=>1]);

        return $this->success('success',$result);
    }


    public function lists(){
        $result = $this->app(SysSecondService::class)->searchExp(['status'=>1]);
        return $this->success('success',$result);
    }


    public function info(){
        $market  = $this->request->input('market','btcusdt');
        $result = $this->app(SysSecondService::class)->searchApi($market);
        if(!$result){ return $this->fail('fail');}
        $now_m_s = date('H:i'); $configService= $this->app(SysConfigService::class);
        $result['scene'] =  $this->service->checkScene($now_m_s,$configService);
        if($result['scene'] == 2){
            $result['num_limit'] =  explode('@', $configService->value('second_preserv_num'));//场数量限制
            $result['win_rate'] = $configService->value('second_preserv_win');//盈利比例
            $result['rate_lose'] =   $configService->value('second_preserv_lose');// 亏损比例
            $result['trade_period_arr'] =  ["60"];
        }elseif($result['scene'] == 1) {
            $result['num_limit'] = explode('@', $configService->value('second_common_num'));// 场数量限制
            $result['win_rate'] = $configService->value('second_common_win');// 盈利比例
            $result['rate_lose'] = $configService->value('second_common_lose');// 亏损比例
            $result['trade_period_arr'] =  ["60"];
        }else{
            $result['num_limit'] = explode('@', $configService->value('no_second_common_num'));// 场数量限制
            $result['win_rate'] = $configService->value('no_second_common_win');// 盈利比例
            $result['rate_lose'] = $configService->value('no_second_common_lose');// 亏损比例
        }
        $result['now_time'] = time();
        $result['now_mill_time'] = $this->get_millisecond();

        return $this->success('success',$result);
    }

     public function timestamp(){

        $result['now_time'] = time();
        $result['now_mill_time'] = $this->get_millisecond();
        return $this->success('success',$result);
    }


    /**
     * 跟单下单
     */
    public function create()
    {
        $userId = $this->request->query('userId');
        if(!$this->limitIp($this->request->query('ip'),'ip_lock_second_create_' . $userId,1)){
            return $this->fail('try_later'); //操作频繁，稍后尝试！
        }
        $data = $this->request->inputs(['market','direct','period','number']);
        $this->validated($data, \App\Validation\Api\SecondOrderValidation::class);
        // 添加
        $res = $this->service->create($this->request->query('userId'),$data['market'],$data['direct'],$data['period'],$data['number']);
        if(!$res){
            return $this->fail('fail');
        }
        $res['now_time'] = time();
        return $this->success('success',$res);
    }


    /*跟单记录-全部*/
    public function order()
    {
        $where['user_id'] = $this->request->query('userId');

        $where['second_id'] = $this->request->query('second_id');

        $where['settle'] = $this->request->query('settle');

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,['second:id,market,status'],$page,$perPage);

        return $this->success('success',$lists);
    }

    /*跟单记录-不分页*/
    public function logs()
    {
        $where['user_id'] = $this->request->query('userId');

        $where['second_id'] = $this->request->query('second_id');

        $where['settle'] = $this->request->query('settle');

        $limit = $this->request->query('limit',10);

        $lists  = $this->service->searchApi($where,$limit);

        return $this->success('success',$lists);
    }

    /*跟单记录-盈利-真假参合*/
    public function win()
    {
        $where['second_id'] = $this->request->query('second_id');
        $limit = $this->request->query('limit',10);
        $lists  = $this->service->searchWin($where,$limit);
        return $this->success('success',$lists);
    }


    /*订单详情*/
    public function statis(){
        $id  = $this->request->input('id');
        $result =  $this->service->findWith($id,['user:id,username','second:id,market,status']);
        return $this->success('success',$result);
    }

    /**
     * 结算记录
     */
    public function income()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_type'=>1,'second_id'=>$this->request->query('second_id','')];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserSecondIncomeService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);
    }

    /**
     * 动态记录
     */
    public function dnamic()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_type'=>1];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserSecondQuickenService::class)->search($where,$page,$perPage);

        return $this->success('success',$lists);
    }

    /**
     * 团队记录
     */
    public function groups()
    {
        $where= ['user_id'=>$this->request->query('userId'),'reward_type'=>2];

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(UserSecondQuickenService::class)->search($where,$page,$perPage);

        $lists->each(function ($value){

            $now = Carbon::now()->setDate(date("Y", $value['reward_time']) , date("m", $value['reward_time']), date("d", $value['reward_time']));
            $startweek = $now->startOfWeek()->toDateString(); $endsweek = $now->endOfWeek()->toDateString();
            $ewai = $this->app(UserBalanceLogService::class)->getQuery()->where('user_id',$value['user_id'])->where('created_at','>=',$startweek)->where('created_at','<=',$endsweek)->where('type',19)->sum('num');
            $value['ewai'] = $ewai > 0 ? $ewai : 0;
            return $value;
        });


        return $this->success('success',$lists);
    }


}
