<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ImgsValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '标题',
            'image' => '轮播图',
            'type' => '类型',
            'method' => '跳转方式',
            'sort' => '排序',
            'url' => '地址',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'title' => 'required',
            'image' => 'required|url',
            'type' => 'required|integer',
            'method' => 'required|integer',
            'sort' => 'required|integer',
            'url' => 'filled',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'title.required' => ':attribute不能为空',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'type.required' => ':attribute不能为空',
            'type.integer' => ':attribute格式错误',
            'method.required' => ':attribute不能为空',
            'method.integer' => ':attribute格式错误',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute格式错误',
            'url.filled' => ':attribute不能为空'
        ];
    }

}
