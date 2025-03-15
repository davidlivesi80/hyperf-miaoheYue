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
     SysConfigService,
};

class ConfigController extends BaseController
{

    public function lists()
    {
        $keys = $this->request->input('keys','');
        $keys =   $keys ? explode(',', $keys) : [];
        $result = $this->app(SysConfigService::class)->getQuery()->whereIn('key', $keys)->pluck('value', 'key');
        return $this->success('success', $result);
    }

    public function kline()
    {
        $kline_price = $this->app(SysConfigService::class)->getQuery()->where('key', 'kline_price')->value('value');
        $kline_price = explode('@',$kline_price);
        $date1 = date("m-d");
        $date2 = date("m-d", strtotime('-1 day'));
        $date3 = date("m-d", strtotime('-2 day'));
        $date4 = date("m-d", strtotime('-3 day'));
        $date5 = date("m-d", strtotime('-4 day'));
        $kline_date = [$date5,$date4,$date3,$date2,$date1];
        return $this->success('success', compact('kline_price','kline_date'));
    }

}
