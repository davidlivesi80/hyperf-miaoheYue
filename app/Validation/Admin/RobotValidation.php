<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class RobotValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '名称',
            'price' => '单价',
            'rate' => '释放',
            'lever' => '杠杆',
            'image' => '封面',
            'detail' => '说明文字',
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
            'rate' => 'required|numeric',
            'lever' => 'required|numeric',
            'image' => 'required|url',
            'detail' => 'required',
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
            'rate.required' => ':attribute不能为空',
            'rate.numeric' => ':attribute只能是数字',
            'lever.required' => ':attribute不能为空',
            'lever.numeric' => ':attribute只能是整数',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'detail.required' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
        ];
    }

}
