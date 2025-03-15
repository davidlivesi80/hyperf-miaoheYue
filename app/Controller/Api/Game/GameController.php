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
namespace App\Controller\Api\Game;

use Upp\Basic\BaseController;
use Upp\Service\SmsService;
use App\Common\Service\Users\{UserRobotService, UserRelationService, UserService, UserGameService};
use App\Common\Service\System\{SysGameService};
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\DbConnection\Db;

class GameController extends BaseController
{

    /**
     * @var SysGameService
     */
    private  $service;

    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator,SysGameService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    public function lists()
    {
        $lists  = $this->service->search(['status'=>1]);
        return $this->success('请求成功',$lists);
    }

    public function info($id){

        $game = $this->service->find($id);
        if(!$game){
            return $this->fail('游戏不存在');
        }
        if($game->id != 37){
            return $this->fail('游戏暂未开放');
        }
        if($game->status != 1){
            return $this->fail('游戏暂未开放');
        }
        $user = $this->app(UserService::class)->find($this->request->query('userId'));
        $data ['username'] = $user->username;
        $data ['gamename'] = $game->name;
        $data ['sign'] = $this->service->makesign($data);
        $uri = $game->url . "/api.php/index/login";
        $result = $this->service->requestGame($uri,$data);
        return $this->success('请求成功',$result);
    }

    public function balance($id){
        $game = $this->service->find($id);
        if(!$game){
            return $this->fail('游戏不存在');
        }
        if($game->id != 37){
            return $this->fail('游戏暂未开放');
        }
        if($game->status != 1){
            return $this->fail('游戏暂未开放');
        }

        $user = $this->app(UserService::class)->find($this->request->query('userId'));
        $gameUser ['username'] = $user->username;
        $gameUser ['gamename'] = $game->name;
        $gameUser ['sign'] = $this->service->makesign($gameUser);
        $uri = $game->url . "/api.php/index/checkuser";
        $result = $this->app(SysGameService::class)->requestGame($uri,$gameUser);
        if(!$result){
            return $this->fail('游戏角色不存在');
        }
        unset($result['token']);unset($result['password']);
        return $this->success('请求成功',$result);
    }
    



}
