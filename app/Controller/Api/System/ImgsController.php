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
     SysImgsService
};

class ImgsController extends BaseController
{

    public function lists()
    {
        $lang = $this->request->input('lang');
        $type = $this->request->input('type',1);
        if(!$this->app(SysImgsService::class)->getQuery()->where(['type'=>$type,'status'=>1,'lang'=>$lang])->count()){
            $lang = 'en';
        }
        $where = ['type' =>$type,'status'=>1,'lang'=>$lang];
        $result = $this->app(SysImgsService::class)->searchApi($where);
        return $this->success('success', $result);
    }

    public function info()
    {
        $type = $this->request->input('type');
        $lang = $this->request->input('lang');
        if(!$this->app(SysImgsService::class)->getQuery()->where(['type'=>$type,'lang'=>$lang])->count()){
            $lang = 'en';
        }
        $result = $this->app(SysImgsService::class)->searchApi(['type'=>$type,'lang'=>$lang]);
        $langs = array_column($result,'lang');
        $key = array_search($lang,$langs);
        return $this->success('success', $key !== false ? $result[$key] : '');

    }


}
