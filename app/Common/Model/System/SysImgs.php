<?php

declare (strict_types=1);

namespace App\Common\Model\System;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class SysImgs extends BaseModel
{
    use SoftDeletes;
    /**
     * 自定义属性
     */
    protected $appends  = ['img','opentype'];

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
        return 'sys_imgs';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'title','image','url','type','method','status','sort','lang'
        ];
    }

    /**
     * @return string
     */
    public function getImgAttribute()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getOpentypeAttribute()
    {
        return 'navigate';
    }


}