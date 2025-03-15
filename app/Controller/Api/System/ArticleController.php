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
    SysArticleService
};


class ArticleController extends BaseController
{


    public function articleCate()
    {

        $result = $this->app(SysArticleService::class)->getAllCate();

        return $this->success('请求成功', $result);

    }

    public function articleList()
    {
        $where = ['cate' => $this->request->input('cate'),'lang'=>$this->request->input('lang','en')];

        if(!$this->app(SysArticleService::class)->getQuery()->where(['cate'=>$where['cate'],'lang'=>$where['lang']])->count()){
            $where['lang'] = 'en';
        }

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $result = $this->app(SysArticleService::class)->search($where, $page, $perPage);

        return $this->success('success', $result);

    }

    public function articleHost()
    {
        $where = ['cate' => $this->request->input('cate'), 'recommend' => 1,'lang'=>$this->request->input('lang','en')];

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $result = $this->app(SysArticleService::class)->searchApi($where, $page, $perPage);

        return $this->success('success', $result);

    }

    public function articleInfo()
    {
        $cate = $this->request->input('cate');
        $lang = $this->request->input('lang');
        $lunb = $this->request->input('lunb',0);//轮播ID
        if(!$this->app(SysArticleService::class)->getQuery()->where(['cate'=>$cate,'lunb'=>$lunb,'lang'=>$lang])->count()){
            $lang = 'en';
        }
        $result = $this->app(SysArticleService::class)->searchApi(['cate'=>$cate,'lunb'=>$lunb,'lang'=>$lang]);
        $langs = array_column($result,'lang');
        $key = array_search($lang,$langs);
        return $this->success('success', $key !== false ? $result[$key] : '');
    }
    

    
    public function articleTaned()
    {
        $lang = $this->request->input('lang');

        if(!$this->app(SysArticleService::class)->getQuery()->where(['taned'=>1,'cate'=>2,'lang'=>$lang])->count()){
            $lang = 'en';
        }
        $result = $this->app(SysArticleService::class)->searchApi(['taned'=>1,'cate'=>2,'lang'=>$lang]);

        $langs = array_column($result,'lang');
        $key = array_search($lang,$langs);
        return $this->success('success',$key !== false ? $result[$key] : '');

    }


}
