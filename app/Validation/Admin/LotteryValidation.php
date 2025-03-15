<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class LotteryValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '名称',
            'price' => '单价',
            'number' => '数量',
            'types' => '类型',
            'image' => '封面',
            'sort'   => '排序'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'title' => 'required',
            'price' => 'required|numeric',
            'number' => 'required|numeric',
            'types' => 'required|numeric',
            'image' => 'required|url',
            'sort' => 'required|integer',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'title.required' => ':attribute不能为空',
            'price.required' => ':attribute不能为空',
            'price.numeric' => ':attribute只能是整数',
            'number.required' => ':attribute不能为空',
            'number.numeric' => ':attribute只能是数字',
            'types.required' => ':attribute不能为空',
            'types.numeric' => ':attribute只能是整数',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
        ];
    }

}
