<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ArticleValidation
{
    public static function attrs (): array
    {
        return[
            'cate' => '分类',
            'title' => '标题',
            'image' => '轮播图',
            'details' => '简介',
            'sort' => '排序',
            'content' => '内容',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'cate' => 'required|integer',
            'title' => 'required',
            'image' => 'required|url',
            'details' => 'required',
            'sort' => 'required|integer',
            'content' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'cate.required' => ':attribute不能为空',
            'cate.integer' => ':attribute格式错误',
            'title.required' => ':attribute不能为空',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'details.required' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute格式错误',
            'content.required' => ':attribute不能为空',
        ];
    }


}
