<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class SafetyValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '名称',
            'price' => '价格',
            'sort'   => '排序',
            'period'=> '周期',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'title' => 'required',
            'price' => 'required',
            'sort' => 'required|integer',
            'period' => 'required|numeric',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'symbol.required' => ':attribute不能为空',
            'price.required' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
            'period.required' => ':attribute不能为空',
            'period.numeric' => ':attribute只能是数字',
        ];
    }

}
