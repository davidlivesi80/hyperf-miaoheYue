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
use App\Common\Service\System\{SysVersionService};

class VersionController extends BaseController
{

    public function info($os)
    {

        if ($os == 'Google') {
            $type = 0;
        } elseif($os == 'Apple') {
            $type = 1;
        } else{
            return $this->fail('param_error');
        }

        $result  = $this->app(SysVersionService::class)->findwhere('type',$type);

        return $this->success('success',$result);

    }



}
