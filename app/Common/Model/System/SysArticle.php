<?php

declare (strict_types=1);
namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysArticle extends BaseModel
{
    use SoftDeletes;
    /**
     * 自定义属性
     */
    protected $appends  = ['url','opentype'];

    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'sys_article';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'cate','title','image','details','content','lang','sort'
        ];
    }

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return '/pages/conts/detail?id=' . $this->id;
    }


    /**
     * @return string
     */
    public function getOpentypeAttribute()
    {
        return 'navigate';
    }


}