<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class SportMatchValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '标题',
            'name' => '符号',
            'image' => '轮播图',
            'detail' => '简介',
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
            'name' => 'required|alpha_num',
            'image' => 'required|url',
            'detail' => 'required',
            'sort' => 'required|integer',
            'url' => 'required|url',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'title.required' => ':attribute不能为空',
            'name.required' => ':attribute不能为空',
            'name.alpha_num' => ':attribute格式为英文(数字)组合',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'detail.required' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute格式错误',
            'url.required' => ':attribute不能为空',
            'url.url' => ':attribute格式错误',
        ];
    }

}
