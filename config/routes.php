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
use Hyperf\HttpServer\Router\Router;
use App\Middleware\Api\LogMiddleware;
use App\Middleware\Api\ReqMiddleware;
use App\Middleware\Api\SignMiddleware;
use App\Middleware\Api\WallteMiddleware;
use App\Middleware\Api\LangMiddleware;
use App\Middleware\Api\LoginMiddleware;
use App\Middleware\Api\IpWhiteMiddleware;
use App\Middleware\Api\LimitMiddleware;
use App\Middleware\Api\CaptchaMiddleware;
use App\Middleware\Api\UpgradeMiddleware;
use App\Middleware\Api\FoundMiddleware;
use App\Middleware\Api\CheckMiddleware;
use App\Middleware\Sys\AuthMiddleware;
use App\Middleware\Sys\AdminMiddleware;
use App\Middleware\Sys\AdminLogMiddleware;
use App\Middleware\WebSocketAuthMiddleware;


/*********************************************************api
 *-----------------------------Sys模块--------------------
 *********************************************************/
Router::addGroup('/sys',function (){

    Router::post('/job', 'App\Controller\Sys\Main\IndexController@job');

    Router::post('/login', 'App\Controller\Sys\Main\LoginController@index');

    Router::post('/notify', 'App\Controller\Sys\Main\LoginController@notify',['middleware' => [IpWhiteMiddleware::class,AdminLogMiddleware::class]]);

    /*无需权限*/
    Router::addGroup('',function (){
        Router::get('/user','App\Controller\Sys\Manage\ManageController@index');
        Router::post('/pass','App\Controller\Sys\Manage\ManageController@password',['middleware' => [AdminLogMiddleware::class]]);
        Router::addRoute(['GET', 'POST'],'/logout','App\Controller\Sys\System\IndexController@logout');
    }, ['middleware' => [AdminMiddleware::class]]);

    /*验证权限*/
    Router::addGroup('',function (){

        Router::get('/counts-user','App\Controller\Sys\System\IndexController@countsUser');
        Router::get('/counts-orders','App\Controller\Sys\System\IndexController@countsOrders');
        Router::get('/counts-withdraw','App\Controller\Sys\System\IndexController@countsWithdraw');
        Router::get('/counts-pools','App\Controller\Sys\System\IndexController@countsPools');
        Router::get('/counts-safety','App\Controller\Sys\System\IndexController@countsSafety');
        Router::get('/counts-balance','App\Controller\Sys\System\IndexController@countsBalance');
        Router::get('/counts-robot','App\Controller\Sys\System\IndexController@countsRobot');
        Router::get('/counts-reward','App\Controller\Sys\System\IndexController@countsReward');
        Router::get('/counts-surplus','App\Controller\Sys\System\IndexController@countsSurplus');
        Router::get('/counts-power','App\Controller\Sys\System\IndexController@countsPower');
        Router::get('/counts-lottery','App\Controller\Sys\System\IndexController@countsLottery');

        Router::addGroup('/menus/',function (){
            Router::get('list','App\Controller\Sys\Manage\MenusController@lists');
            Router::post('create','App\Controller\Sys\Manage\MenusController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Manage\MenusController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Manage\MenusController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/role/',function (){
            Router::get('list','App\Controller\Sys\Manage\GroupController@lists');
            Router::get('auth/{id}','App\Controller\Sys\Manage\GroupController@auth');
            Router::post('create','App\Controller\Sys\Manage\GroupController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Manage\GroupController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('give/{id}','App\Controller\Sys\Manage\GroupController@give',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('batch','App\Controller\Sys\Manage\GroupController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Manage\GroupController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/manage/',function (){
            Router::get('list','App\Controller\Sys\Manage\ManageController@lists');
            Router::post('create','App\Controller\Sys\Manage\ManageController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Manage\ManageController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('batch','App\Controller\Sys\Manage\ManageController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Manage\ManageController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('check','App\Controller\Sys\Manage\ManageController@check');
            Router::get('reset/{id}','App\Controller\Sys\Manage\ManageController@reset',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Manage\ManageController@status',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/adminlogs/',function (){
            Router::get('list','App\Controller\Sys\Manage\LogsController@lists');
            Router::post('batch','App\Controller\Sys\Manage\LogsController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('clear','App\Controller\Sys\Manage\LogsController@clear');
        });

        Router::addGroup('/user/',function (){
            Router::get('demo','App\Controller\Sys\Users\UserController@demo');
            Router::get('list','App\Controller\Sys\Users\UserController@lists');
            Router::get('sorts','App\Controller\Sys\Users\UserController@sorts');
            Router::get('union','App\Controller\Sys\Users\UserController@union');
            Router::get('counts','App\Controller\Sys\Users\UserController@counts');
            Router::get('groups','App\Controller\Sys\Users\UserController@groups');
            Router::get('child','App\Controller\Sys\Users\UserController@child');
            Router::post('create','App\Controller\Sys\Users\UserController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Users\UserController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('wallet/{id}','App\Controller\Sys\Users\UserController@wallet',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('export/{id}','App\Controller\Sys\Users\UserController@export');
            Router::post('batch','App\Controller\Sys\Users\UserController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Users\UserController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Users\UserController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('check','App\Controller\Sys\Users\UserController@check');
            Router::post('withdraw/{id}','App\Controller\Sys\Users\UserController@withdraw',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('withdrow','App\Controller\Sys\Users\UserController@withdrow',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('autodraw/{id}','App\Controller\Sys\Users\UserController@autodraw',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('reward/{id}','App\Controller\Sys\Users\UserController@reward',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('raward/{id}','App\Controller\Sys\Users\UserController@raward',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('malice/{id}','App\Controller\Sys\Users\UserController@malice',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('duidou/{id}','App\Controller\Sys\Users\UserController@duidou',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('duidou-extend/{id}','App\Controller\Sys\Users\UserController@duidouExtend',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('clear-bind/{id}','App\Controller\Sys\Users\UserController@clearBind',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('clear-google/{id}','App\Controller\Sys\Users\UserController@clearGoogle',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('move','App\Controller\Sys\Users\UserController@moveRelation',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('link','App\Controller\Sys\Users\UserController@link');
            Router::get('found','App\Controller\Sys\Users\UserController@found');
            Router::post('sets','App\Controller\Sys\Users\UserController@sets',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('leader','App\Controller\Sys\Users\UserController@leader');
            Router::get('relation','App\Controller\Sys\Users\UserController@relation');
        });

        Router::addGroup('/balance/',function (){
            Router::get('list','App\Controller\Sys\Balan\BalanceController@lists');
            Router::get('type','App\Controller\Sys\Balan\BalanceController@type');
            Router::get('logs','App\Controller\Sys\Balan\BalanceController@logs');
            Router::post('create','App\Controller\Sys\Balan\BalanceController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('wallet/{id}','App\Controller\Sys\Balan\BalanceController@wallet',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('transfer','App\Controller\Sys\Balan\BalanceController@transfer');
            Router::get('recharge','App\Controller\Sys\Balan\BalanceController@recharge');
            Router::post('recharge-yes/{id}','App\Controller\Sys\Balan\BalanceController@rechargeYes',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('recharge-nos/{id}','App\Controller\Sys\Balan\BalanceController@rechargeNos',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('recharge-ups/{id}','App\Controller\Sys\Balan\BalanceController@rechargeUps',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('withdraw','App\Controller\Sys\Balan\BalanceController@withdraw');
            Router::get('withdraw-order-new','App\Controller\Sys\Balan\BalanceController@withdrawOrderNewId');
            Router::post('withdraw-yes/{id}','App\Controller\Sys\Balan\BalanceController@withdrawYes',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('withdraw-nos/{id}','App\Controller\Sys\Balan\BalanceController@withdrawNos',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('withdraw-aud/{id}','App\Controller\Sys\Balan\BalanceController@withdrawAud',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('withdraw-reset/{id}','App\Controller\Sys\Balan\BalanceController@withdrawReset',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('withdraw-rules','App\Controller\Sys\Balan\BalanceController@withdrawRules');
            Router::get('wallet-balance','App\Controller\Sys\Balan\BalanceController@walletBalance');
            Router::get('exports','App\Controller\Sys\Balan\BalanceController@exports');
            Router::get('logs-exports','App\Controller\Sys\Balan\BalanceController@logsExports');
            Router::get('transfer-exports','App\Controller\Sys\Balan\BalanceController@transferExports');
            Router::get('withdraw-exports','App\Controller\Sys\Balan\BalanceController@withdrawExports');
            Router::get('recharge-exports','App\Controller\Sys\Balan\BalanceController@RechargeExports');
        });

        Router::addGroup('/exchange/',function (){
            Router::get('list','App\Controller\Sys\Balan\ExchangeController@lists');
            Router::post('create','App\Controller\Sys\Balan\ExchangeController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Balan\ExchangeController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Balan\ExchangeController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Balan\ExchangeController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('order','App\Controller\Sys\Balan\ExchangeController@order');
        });

        Router::addGroup('/otc/',function (){
            Router::get('list','App\Controller\Sys\Otc\OtcController@lists');
            Router::post('create','App\Controller\Sys\Otc\OtcController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Otc\OtcController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Otc\OtcController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('publist','App\Controller\Sys\Otc\OtcController@publist');
            Router::addRoute(['GET', 'POST'],'remove/{userId}/{id}','App\Controller\Sys\Otc\OtcController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('poplist','App\Controller\Sys\Otc\OtcController@poplist');
        });

        Router::addGroup('/message/',function (){
            Router::get('list','App\Controller\Sys\Users\MessageController@lists');
            Router::post('reply/{id}','App\Controller\Sys\Users\MessageController@reply',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('batch','App\Controller\Sys\Users\MessageController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Users\MessageController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/bank/',function (){
            Router::get('list','App\Controller\Sys\Users\BankController@lists');
            Router::post('update/{id}','App\Controller\Sys\Users\BankController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\Users\BankController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });


        Router::addGroup('/config/',function (){
            Router::get('list','App\Controller\Sys\System\ConfigController@lists');
            Router::get('keys','App\Controller\Sys\System\ConfigController@keys');
            Router::post('create','App\Controller\Sys\System\ConfigController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\ConfigController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('valued/{id}','App\Controller\Sys\System\ConfigController@valued',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('types','App\Controller\Sys\System\ConfigController@types');
            Router::get('eles','App\Controller\Sys\System\ConfigController@eles');
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\ConfigController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/files/',function (){
            Router::get('list','App\Controller\Sys\System\FilesController@lists');
            Router::get('cate','App\Controller\Sys\System\FilesController@cate');
            Router::post('batch','App\Controller\Sys\System\FilesController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('up-image/{id}/{filed}','App\Controller\Sys\System\FilesController@uploadImage',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('up-files/{id}/{filed}','App\Controller\Sys\System\FilesController@uploadFiles',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('logs','App\Controller\Sys\System\FilesController@logs');
            Router::get('clear','App\Controller\Sys\System\FilesController@clear');
        });

        Router::addGroup('/article/',function (){
            Router::get('cate','App\Controller\Sys\System\ArticleController@cate');
            Router::get('list','App\Controller\Sys\System\ArticleController@lists');
            Router::post('create','App\Controller\Sys\System\ArticleController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\ArticleController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('batch','App\Controller\Sys\System\ArticleController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\ArticleController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\System\ArticleController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('recommend/{id}','App\Controller\Sys\System\ArticleController@recommend',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('taned/{id}','App\Controller\Sys\System\ArticleController@taned');
            Router::get('tanls','App\Controller\Sys\System\ArticleController@tanls');
        });

        Router::addGroup('/imgs/',function (){
            Router::get('list','App\Controller\Sys\System\ImgsController@lists');
            Router::get('types','App\Controller\Sys\System\ImgsController@types');
            Router::get('method','App\Controller\Sys\System\ImgsController@method');
            Router::post('create','App\Controller\Sys\System\ImgsController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\ImgsController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('batch','App\Controller\Sys\System\ImgsController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\ImgsController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\System\ImgsController@status',['middleware' => [AdminLogMiddleware::class]]);
        });

    
        Router::addGroup('/email/',function (){
            Router::get('list','App\Controller\Sys\System\EmailController@lists');
            Router::post('create','App\Controller\Sys\System\EmailController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\EmailController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\EmailController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('send/{id}','App\Controller\Sys\System\EmailController@send',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/version/',function (){
            Router::get('list','App\Controller\Sys\System\VersionController@lists');
            Router::post('update/{id}','App\Controller\Sys\System\VersionController@update',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/logs/',function (){
            Router::get('list','App\Controller\Sys\System\LogsController@lists');
            Router::post('batch','App\Controller\Sys\System\LogsController@batch',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('clear','App\Controller\Sys\System\LogsController@clear',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/crontab/',function (){
            Router::get('list','App\Controller\Sys\System\CrontabController@lists');
            Router::post('create','App\Controller\Sys\System\CrontabController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\CrontabController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\CrontabController@remove',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\System\CrontabController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('clear','App\Controller\Sys\System\CrontabController@clear',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/contract/',function (){
            Router::get('list','App\Controller\Sys\System\ContractController@lists');
            Router::post('create','App\Controller\Sys\System\ContractController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\ContractController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\ContractController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/coins/',function (){
            Router::get('list','App\Controller\Sys\System\CoinsController@lists');
            Router::get('luck','App\Controller\Sys\System\CoinsController@lista');
            Router::post('create','App\Controller\Sys\System\CoinsController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\System\CoinsController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'remove/{id}','App\Controller\Sys\System\CoinsController@remove',['middleware' => [AdminLogMiddleware::class]]);
        });

        Router::addGroup('/second/',function (){
            Router::get('list','App\Controller\Sys\Second\SecondController@lists');
            Router::get('types','App\Controller\Sys\Second\SecondController@types');
            Router::post('create','App\Controller\Sys\Second\SecondController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Second\SecondController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Second\SecondController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('tops/{id}','App\Controller\Sys\Second\SecondController@tops',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('order','App\Controller\Sys\Second\SecondController@order');
            Router::post('order-create','App\Controller\Sys\Second\SecondController@orderCreate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('order-update/{id}','App\Controller\Sys\Second\SecondController@orderUpdate',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('order-count','App\Controller\Sys\Second\SecondController@orderCount');
            Router::get('order-minute','App\Controller\Sys\Second\SecondController@orderMinute');
            Router::get('income','App\Controller\Sys\Second\SecondController@income');
            Router::get('reward','App\Controller\Sys\Second\SecondController@reward');
            Router::get('reward-exports','App\Controller\Sys\Second\SecondController@rewardExports');
            Router::get('kline','App\Controller\Sys\Second\SecondController@kline');
            Router::post('kline-create','App\Controller\Sys\Second\SecondController@klineCreate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('kline-update/{id}','App\Controller\Sys\Second\SecondController@klineUpdate',['middleware' => [AdminLogMiddleware::class]]);
            Router::addRoute(['GET', 'POST'],'kline-remove/{id}','App\Controller\Sys\Second\SecondController@klineRemove',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('kol','App\Controller\Sys\Second\SecondController@kol');

        });

        Router::addGroup('/safety/',function (){
            Router::get('list','App\Controller\Sys\Safety\SafetyController@lists');
            Router::get('types','App\Controller\Sys\Safety\SafetyController@types');
            Router::post('create','App\Controller\Sys\Safety\SafetyController@create',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('update/{id}','App\Controller\Sys\Safety\SafetyController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('status/{id}','App\Controller\Sys\Safety\SafetyController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('logs','App\Controller\Sys\Safety\SafetyController@logs');
            Router::get('order','App\Controller\Sys\Safety\SafetyController@order');
            Router::post('order-create','App\Controller\Sys\Safety\SafetyController@orderCreate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('order-update/{id}','App\Controller\Sys\Safety\SafetyController@orderUpdate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('order-close/{id}','App\Controller\Sys\Safety\SafetyController@orderClose',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('order-self','App\Controller\Sys\Safety\SafetyController@orderSelf',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('order-count','App\Controller\Sys\Safety\SafetyController@orderCount');
            Router::get('coupons','App\Controller\Sys\Safety\SafetyController@coupons');
            Router::post('coupons-create','App\Controller\Sys\Safety\SafetyController@couponsCreate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('coupons-update/{id}','App\Controller\Sys\Safety\SafetyController@couponsUpdate',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('reward','App\Controller\Sys\Safety\SafetyController@reward');

        });

        Router::addGroup('/robot/',function (){
            Router::get('order','App\Controller\Sys\Robot\RobotController@order');
            Router::post('order-create','App\Controller\Sys\Robot\RobotController@orderCreate',['middleware' => [AdminLogMiddleware::class]]);
            Router::post('order-update/{id}','App\Controller\Sys\Robot\RobotController@orderUpdate',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('income','App\Controller\Sys\Robot\RobotController@income',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('reward','App\Controller\Sys\Robot\RobotController@reward',['middleware' => [AdminLogMiddleware::class]]);
        });

//        Router::addGroup('/lottery/',function (){
//            Router::get('list','App\Controller\Sys\Lottery\LotteryController@lists');
//            Router::get('luck','App\Controller\Sys\Lottery\LotteryController@luck');
//            Router::post('create','App\Controller\Sys\Lottery\LotteryController@create');
//            Router::post('update/{id}','App\Controller\Sys\Lottery\LotteryController@update');
//            Router::post('sets/{id}','App\Controller\Sys\Lottery\LotteryController@sets');
//            Router::post('status/{id}','App\Controller\Sys\Lottery\LotteryController@status');
//            Router::post('show/{id}','App\Controller\Sys\Lottery\LotteryController@show');
//            Router::get('attres','App\Controller\Sys\Lottery\LotteryController@attres');
//            Router::post('attr-create','App\Controller\Sys\Lottery\LotteryController@attrCreate');
//            Router::post('attr-update/{id}','App\Controller\Sys\Lottery\LotteryController@attrUpdate');
//            Router::get('attr-unit','App\Controller\Sys\Lottery\LotteryController@attrUnit');
//            Router::get('order','App\Controller\Sys\Lottery\LotteryController@order');
//            Router::post('order-create','App\Controller\Sys\Lottery\LotteryController@orderCreate');
//            Router::post('order-update/{id}','App\Controller\Sys\Lottery\LotteryController@orderUpdate');
//            Router::get('income','App\Controller\Sys\Lottery\LotteryController@income');
//        });

        Router::addGroup('/power/',function (){
            Router::get('list','App\Controller\Sys\Power\PowerController@lists');
            Router::post('update/{id}','App\Controller\Sys\Power\PowerController@update',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('order','App\Controller\Sys\Power\PowerController@order');
            Router::get('logs','App\Controller\Sys\Power\PowerController@logs');
            Router::get('type','App\Controller\Sys\Power\PowerController@type');
            Router::post('status/{id}','App\Controller\Sys\Power\PowerController@status',['middleware' => [AdminLogMiddleware::class]]);
            Router::get('income','App\Controller\Sys\Power\PowerController@income',['middleware' => [AdminLogMiddleware::class]]);
        });

    }, ['middleware' => [AdminMiddleware::class, AuthMiddleware::class]]);

});


/*********************************************************api
 *-----------------------------api模块--------------------
 *********************************************************/
Router::addGroup('/api',function (){

    Router::post('/req-token','App\Controller\Api\Main\PublicController@reqToken');

    Router::post('/req-demos','App\Controller\Api\Main\PublicController@reqDemos');

    Router::post('/get_null_info','App\Controller\Api\Main\PublicController@index');

    Router::get('/version/{os}','App\Controller\Api\System\VersionController@info');

    Router::get('/second/query','App\Controller\Api\Second\SecondController@lists',['middleware' => [LimitMiddleware::class]]);

    Router::addGroup('/main/',function (){
        /*登录、注册、密码、短信、邮件*/
        Router::post('send-sms','App\Controller\Api\Main\PublicController@sendSms',['middleware'=>[LogMiddleware::class,LimitMiddleware::class,CaptchaMiddleware::class]]);

        Router::post('send-ems','App\Controller\Api\Main\PublicController@sendEms',['middleware'=>[LogMiddleware::class,LimitMiddleware::class,CaptchaMiddleware::class]]);

        Router::get('get-captcha','App\Controller\Api\Main\PublicController@getCaptcha',['middleware'=>[LimitMiddleware::class]]);

        Router::post('login','App\Controller\Api\Main\PublicController@login',['middleware'=>[LogMiddleware::class,LimitMiddleware::class,CaptchaMiddleware::class]]);

        Router::post('regis','App\Controller\Api\Main\PublicController@regis',['middleware'=>[LogMiddleware::class,LimitMiddleware::class,CaptchaMiddleware::class,UpgradeMiddleware::class]]);

        Router::post('forget','App\Controller\Api\Main\PublicController@forget',['middleware'=>[LogMiddleware::class,LimitMiddleware::class]]);

    }, ['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class]]);

    Router::addGroup('/wallet/',function (){

        Router::addGroup('',function (){

            Router::get('address','App\Controller\Api\Main\WalletController@address',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);

            Router::get('check','App\Controller\Api\Main\WalletController@checkToken',['middleware'=>[LimitMiddleware::class,CheckMiddleware::class]]);

        },['middleware' => [ReqMiddleware::class,SignMiddleware::class]]);

        Router::addGroup('',function (){
            //下单、通知
            Router::post('found','App\Controller\Api\Main\WalletController@found');//下单

            Router::post('finds','App\Controller\Api\Main\WalletController@finds');//检索下单

            Router::post('notify','App\Controller\Api\Main\WalletController@notify');//通知

            Router::post('collect','App\Controller\Api\Main\WalletController@collect');//通知

        },['middleware'=>[IpWhiteMiddleware::class,LogMiddleware::class]]);

    });


    Router::addGroup('/system/',function (){
        /*文章、轮播、应用、配置、币种*/
        Router::get('article-cate','App\Controller\Api\System\ArticleController@articleCate');
        Router::get('article-host','App\Controller\Api\System\ArticleController@articleHost');
        Router::get('article-list','App\Controller\Api\System\ArticleController@articleList');
        Router::get('article-info','App\Controller\Api\System\ArticleController@articleInfo');
        Router::get('article-taned','App\Controller\Api\System\ArticleController@articleTaned');

        Router::get('imgs-list','App\Controller\Api\System\ImgsController@lists');
        Router::get('imgs-info','App\Controller\Api\System\ImgsController@info');

        Router::post('config','App\Controller\Api\System\ConfigController@lists');

        Router::get('kline','App\Controller\Api\System\ConfigController@kline');

        Router::get('coins','App\Controller\Api\System\CoinsController@lists');

        Router::get('markets','App\Controller\Api\System\CoinsController@markets');

    }, ['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class]]);

    /*用户基本*/
    Router::addGroup('/user/',function (){
        Router::get('info/[{with}]','App\Controller\Api\Users\UserController@info');
        Router::post('check','App\Controller\Api\Users\UserController@check',['middleware'=>[LogMiddleware::class]]);
        Router::post('password','App\Controller\Api\Users\UserController@password',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::post('paysword','App\Controller\Api\Users\UserController@paysword',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::post('nickname','App\Controller\Api\Users\UserController@nickname',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::post('avatar','App\Controller\Api\Users\UserController@avatar',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::post('bank','App\Controller\Api\Users\UserController@bank',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::post('bindnew','App\Controller\Api\Users\UserController@bindnew',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::get('spread-info','App\Controller\Api\Users\UserController@spreadInfo',['middleware'=>[FoundMiddleware::class]]);
        Router::get('spread-count','App\Controller\Api\Users\UserController@spreadCount',['middleware'=>[FoundMiddleware::class]]);
        Router::get('spread-reward','App\Controller\Api\Users\UserController@spreadReward',['middleware'=>[FoundMiddleware::class]]);
        Router::get('spread-child','App\Controller\Api\Users\UserController@spreadChild',['middleware'=>[FoundMiddleware::class]]);
        Router::post('send','App\Controller\Api\Users\UserController@send',['middleware'=>[LimitMiddleware::class,CaptchaMiddleware::class,FoundMiddleware::class]]);
        Router::post('found','App\Controller\Api\Users\UserController@found');
        Router::post('goole','App\Controller\Api\Users\UserController@goole',['middleware'=>[FoundMiddleware::class]]);
        Router::get('bank-info','App\Controller\Api\Users\UserController@bankInfo');
        /*上传*/
        Router::post('upload/{id}/{filed}','App\Controller\Api\UserController@upload');
        /*留言*/
        Router::get('feedlist','App\Controller\Api\Users\UserController@feedlist');
        Router::post('feedback','App\Controller\Api\Users\UserController@feedback',['middleware'=>[LogMiddleware::class,CaptchaMiddleware::class,FoundMiddleware::class]]);
        Router::get('rank','App\Controller\Api\Users\UserController@rank');
        Router::get('statis','App\Controller\Api\Users\UserController@statis');
    }, ['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*用户资产*/
    Router::addGroup('/balan/',function (){
        Router::get('wallet','App\Controller\Api\Balan\BalanceController@wallet');
        Router::get('info/{coin}','App\Controller\Api\Balan\BalanceController@info');
        Router::get('logs','App\Controller\Api\Balan\BalanceController@logs');
        Router::get('reward','App\Controller\Api\Balan\BalanceController@reward');
        Router::post('recharge','App\Controller\Api\Balan\BalanceController@recharge',['middleware'=>[LogMiddleware::class,FoundMiddleware::class]]);
        Router::get('recharge-logs','App\Controller\Api\Balan\BalanceController@rechargeLogs');
        Router::get('recharge-info','App\Controller\Api\Balan\BalanceController@rechargeInfo');
        Router::post('withdraw','App\Controller\Api\Balan\BalanceController@withdraw',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('withdraw-logs','App\Controller\Api\Balan\BalanceController@withdrawLogs');
        Router::get('withdraw-info','App\Controller\Api\Balan\BalanceController@withdrawInfo');
        Router::post('transfer','App\Controller\Api\Balan\BalanceController@transfer',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('transfer-logs','App\Controller\Api\Balan\BalanceController@transferLogs');
        Router::post('unlock','App\Controller\Api\Balan\BalanceController@unlock',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
    }, ['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*兑换相关*/
    Router::addGroup('/exchange/',function (){
        Router::get('list','App\Controller\Api\Balan\ExchangeController@lists');
        Router::get('info/{id}','App\Controller\Api\Balan\ExchangeController@info');
        Router::post('create','App\Controller\Api\Balan\ExchangeController@create',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('order','App\Controller\Api\Balan\ExchangeController@order');
        Router::get('counts','App\Controller\Api\Balan\ExchangeController@counts');
    },['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*算力资产*/
    Router::addGroup('/power/',function (){
        Router::get('robot','App\Controller\Api\Power\PowerController@robot');
        Router::get('info','App\Controller\Api\Power\PowerController@info');
        Router::post('create','App\Controller\Api\Power\PowerController@create',['middleware' => [LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::post('found','App\Controller\Api\Power\PowerController@found',['middleware' => [LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('order','App\Controller\Api\Power\PowerController@order');
        Router::get('logs','App\Controller\Api\Power\PowerController@logs');
        Router::get('counts','App\Controller\Api\Power\PowerController@counts');
        Router::get('statis','App\Controller\Api\Power\PowerController@statis');
    },['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*跟单相关*/
    Router::addGroup('/second/',function (){
        Router::get('list','App\Controller\Api\Second\SecondController@lists',['middleware' => [LogMiddleware::class]]);
        Router::get('tops','App\Controller\Api\Second\SecondController@tops');
        Router::get('info','App\Controller\Api\Second\SecondController@info');
        Router::post('create','App\Controller\Api\Second\SecondController@create',['middleware' => [UpgradeMiddleware::class]]);
        Router::get('timestamp','App\Controller\Api\Second\SecondController@timestamp');
        Router::get('order','App\Controller\Api\Second\SecondController@order');
        Router::get('logs','App\Controller\Api\Second\SecondController@logs');
        Router::get('win','App\Controller\Api\Second\SecondController@win');
        Router::get('income','App\Controller\Api\Second\SecondController@income');
        Router::get('dnamic','App\Controller\Api\Second\SecondController@dnamic');
        Router::get('groups','App\Controller\Api\Second\SecondController@groups');
    },['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*跟单相关*/
//    Router::addGroup('/lottery/',function (){
//        Router::get('list','App\Controller\Api\Lottery\LotteryController@lists',['middleware' => [LogMiddleware::class]]);
//        Router::get('info','App\Controller\Api\Lottery\LotteryController@info');
//        Router::post('create','App\Controller\Api\Lottery\LotteryController@create',['middleware' => [UpgradeMiddleware::class]]);
//        Router::get('order','App\Controller\Api\Lottery\LotteryController@order');
//        Router::get('logs','App\Controller\Api\Lottery\LotteryController@logs');
//        Router::get('income','App\Controller\Api\Lottery\LotteryController@income');
//        Router::get('dnamic','App\Controller\Api\Lottery\LotteryController@dnamic');
//        Router::get('groups','App\Controller\Api\Lottery\LotteryController@groups');
//    },['middleware' => [LangMiddleware::class,ReqMiddleware::cWlass,SignMiddleware::class,LoginMiddleware::class]]);

    /*保险相关*/
    Router::addGroup('/safety/',function (){
        Router::get('list','App\Controller\Api\Safety\SafetyController@lists',['middleware' => [LogMiddleware::class]]);
        Router::get('info','App\Controller\Api\Safety\SafetyController@info');
        Router::post('create','App\Controller\Api\Safety\SafetyController@create',['middleware' => [LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('order','App\Controller\Api\Safety\SafetyController@order');
        Router::get('logs','App\Controller\Api\Safety\SafetyController@logs');
        Router::get('coupons','App\Controller\Api\Safety\SafetyController@coupons');
        Router::post('send-coupons','App\Controller\Api\Safety\SafetyController@sendCoupons',['middleware' => [LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::post('send-batch','App\Controller\Api\Safety\SafetyController@sendBatch',['middleware' => [LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('reward','App\Controller\Api\Safety\SafetyController@reward');
    },['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);


    /*OTC相关*/
    Router::addGroup('/otc/',function (){
        Router::get('coins','App\Controller\Api\Otc\OtcController@coins');
        Router::get('market','App\Controller\Api\Otc\OtcController@market');
        Router::post('publish','App\Controller\Api\Otc\OtcController@publish',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::post('poplish','App\Controller\Api\Otc\OtcController@poplish',['middleware'=>[LogMiddleware::class,UpgradeMiddleware::class,FoundMiddleware::class]]);
        Router::get('publist','App\Controller\Api\Otc\OtcController@publist');
        Router::get('pubinfo','App\Controller\Api\Otc\OtcController@pubinfo');
        Router::get('enable','App\Controller\Api\Otc\OtcController@enable');
        Router::get('disabe','App\Controller\Api\Otc\OtcController@disabe');
        Router::get('remove','App\Controller\Api\Otc\OtcController@remove');
        Router::get('poplist','App\Controller\Api\Otc\OtcController@poplist');
        Router::get('popinfo','App\Controller\Api\Otc\OtcController@popinfo');
        Router::get('counts','App\Controller\Api\Otc\OtcController@Counts');
    },['middleware' => [LangMiddleware::class,ReqMiddleware::class,SignMiddleware::class,LoginMiddleware::class]]);

    /*渠道相关*/
    Router::addGroup('/node/',function (){
        Router::post('login','App\Controller\Api\Users\NodeController@login',['middleware'=>[LogMiddleware::class]]);
        Router::get('info','App\Controller\Api\Users\NodeController@info',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('child','App\Controller\Api\Users\NodeController@child',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('users','App\Controller\Api\Users\NodeController@users',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('luck','App\Controller\Api\Users\NodeController@luck',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('recharge','App\Controller\Api\Users\NodeController@recharge',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('withdraw','App\Controller\Api\Users\NodeController@withdraw',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('counts-user','App\Controller\Api\Users\NodeController@countsUser',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('counts-withdraw','App\Controller\Api\Users\NodeController@countsWithdraw',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('counts-reward','App\Controller\Api\Users\NodeController@countsReward',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('logs','App\Controller\Api\Users\NodeController@logs',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('relation','App\Controller\Api\Users\NodeController@relation',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
        Router::get('sorts','App\Controller\Api\Users\NodeController@sorts',['middleware'=>[LoginMiddleware::class,FoundMiddleware::class]]);
    },['middleware' => [LangMiddleware::class,SignMiddleware::class]]);

});

// 添加 ws 服务对应的路由
Router::addServer('ws', function () {
    Router::get('/wss/default.io', 'App\Controller\WebSocketController');
});

Router::get('/favicon.ico', function () {
    return '';
});

