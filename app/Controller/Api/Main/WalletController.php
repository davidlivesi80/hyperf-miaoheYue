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
namespace App\Controller\Api\Main;

use App\Common\Service\Users\UserRechargeService;
use Upp\Basic\BaseController;
use App\Common\Service\Users\UserService;
use Upp\Service\ParseToken;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

class WalletController extends BaseController
{

    use HelpTrait;

    public function address()
    {
        $userId = $this->request->query('userId');

        $userName = $this->request->query('userName');

        $token = $this->app(UserService::class)->get_address($userId,$userName);

        return $this->success('success',$token);
    }

    //充值未插入检索
    public function finds()
    {
        $data = $this->request->inputs(['recharge_id']);
        if(!isset($data['recharge_id']) || !is_array($data['recharge_id'])){
            return [ 'code' => 400, 'message' => '参数错误'];
        }

        $result = $this->app(UserRechargeService::class)->getQuery()->whereIn('recharge_id',$data['recharge_id'])->pluck('recharge_id')->toArray();
        if(0 >= count($result)){
            return [ 'code' => 200,'data'=>$result, 'message' => '获取成功'];
        }

        $result = array_diff($data['recharge_id'],$result);
        return [ 'code' => 200, 'data'=>$result, 'message' => '获取成功'];

    }

    //充值订单插入
    public function found()
    {
        $data = $this->request->inputs(['recharge_id','user_id','symbol','amount','series_id','status','tx_id','from','to','create_time']);

        //数据验证
        $this->validated($data,\App\Validation\Api\WalletFoundValidation::class);

        $result = $this->app(UserRechargeService::class)->found($data);

        if(!$result){
            return [ 'code' => 400, 'message' => '插入失败'];
        }
        return [ 'code' => 200, 'message' => '插入成功'];

    }

    //上分通知 1成功 2失败 4已轨迹，未上分，3待确认
    public function notify()
    {

        $data = $this->request->inputs(['recharge_id','status','types']);
        //数据验证
        $this->validated($data,\App\Validation\Api\WalletNotifyValidation::class);
        if($data['types'] == 1){//充值回调
            $result = $this->app(UserRechargeService::class)->finish($data);
        }elseif ($data['types'] == 2){//买卡片
            $result = false;//$this->app(UserCardsService::class)->confirm($data['order_id'],$data);
        }elseif ($data['types'] == 3){//买矿机
            $result = false;//$this->app(UserRobotService::class)->confirm($data['order_id'],$data);
        }
        if(!$result){
            return [ 'code' => 400, 'message' => '上分失败'];
        }
        return [ 'code' => 200, 'message' => '上分成功'];
    }

    //上分通知 归集完成
    public function collect()
    {
        $data = $this->request->inputs(['recharge_id','is_collect']);
        //数据验证
        $this->validated($data,\App\Validation\Api\WalletCollectValidation::class);
        $result = $this->app(UserRechargeService::class)->collect($data);
        if(!$result){
            return [ 'code' => 400, 'message' => '归集失败'];
        }
        return [ 'code' => 200, 'message' => '归集成功'];
    }


    /*切换账号*/
    public function checkToken()
    {

        $userId = $this->request->query('userId');

        $user = Db::table('user')->where('id', $userId)->first();

        $token = $this->app(ParseToken::class)->toToken($user->id,$user->username,'api');

        return $this->success('success',['username'=>$user->username,'token'=>$token['token']]);
    }


}
