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

namespace App\Controller\Api\System;

use Upp\Basic\BaseController;
use App\Common\Service\System\{
 SysCoinsService
};
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

class CoinsController extends BaseController
{

    use HelpTrait;

    public function lists()
    {

        $coin = $this->request->input('coin', '');

        if ($coin) {
            $result = $this->app(SysCoinsService::class)->findWhere('coin_symbol', $coin);
        } else {
            $result = $this->app(SysCoinsService::class)->columns([],['coin_name','coin_symbol','net_id','image']);
        }

        return $this->success('success', $result);

    }
    
    public function markets()
    {

        $markets = Db::table('sys_markets')->get()->toArray();

        foreach ($markets as &$item) {
            $item->rate = $item->price_change;
        }

        return $this->success('success', $markets);

    }

}
