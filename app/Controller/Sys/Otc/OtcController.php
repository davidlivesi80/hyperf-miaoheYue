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
namespace App\Controller\Sys\Otc;

use App\Common\Service\Otc\OtcCoinsService;
use App\Common\Service\Otc\OtcMarketService;
use App\Common\Service\Otc\OtcOrderService;
use App\Common\Service\System\SysCoinsService;
use App\Common\Service\Users\UserRelationService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserPoolsIncomeService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;


class OtcController extends BaseController
{

    /**
     * @var OtcCoinsService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,OtcCoinsService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;

    }

    public function lists()
    {

        $where= $this->request->inputs(['coin_name']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


    /**
     * 权限管理|用户管理@添加用户
     */
    public function create()
    {

        $data = $this->request->all();
        $this->validated($data, \App\Validation\Admin\OtcCoinsValidation::class);
        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin_name']);
        if(!$coin){
            return $this->fail('参数失败');
        }
        $data['coin_id'] = $coin['id'];
        // 添加
        $res = $this->service->create($data);
        if(!$res){
            return $this->fail('添加失败');
        }
        return $this->success('添加成功',$res);

    }

    /**
     * 权限管理|用户管理@添加用户
     */

    public function update($id)
    {

        $data = $this->request->inputs(['coin_name','limit_max_number','limit_min_number','limit_max_price','limit_min_price','max_pub_num','rate']);
        $this->validated($data, \App\Validation\Admin\OtcCoinsValidation::class);
        $coin = $this->app(SysCoinsService::class)->findWhere('coin_symbol',$data['coin_name']);
        if(!$coin){
            return $this->fail('参数失败');
        }
        $data['coin_id'] = $coin['id'];
        // 更新
        $res = $this->service->update($id,$data);
        if(!$res){
            return $this->fail('修改失败');
        }
        return $this->success('修改成功',$res);
    }

    public function status ($id)
    {
        $res = $this->service->updateField($id,'enable',$this->request->input('status'));

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }


    /**
     * 挂单记录
     */
    public function publist()
    {

        $where = $this->request->inputs(['username','otc_coin_id','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(OtcMarketService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


    /*撤销发布*/
    public function remove ($userId,$id)
    {
        $res = $this->app(OtcMarketService::class)->remove($userId,$id);
        if(!$res){
            return $this->fail('操作失败');
        }
        return $this->success('操作成功');
    }

    /**
     * 匹配记录
     */
    public function poplist()
    {

        $where = $this->request->inputs(['username','otc_coin_id','timeStart','timeEnd']);

        $perPage = $this->request->input('limit');

        $page = $this->request->input('page');

        $lists = $this->app(OtcOrderService::class)->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }


}
