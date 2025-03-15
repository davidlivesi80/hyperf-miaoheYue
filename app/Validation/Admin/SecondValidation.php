<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class SecondValidation
{
    public static function attrs (): array
    {
        return[
            'symbol' => '币种',
            'currency' => '计价',
            'sort'   => '排序',
            'trade_period'=> '周期',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'symbol' => 'required',
            'currency' => 'required',
            'sort' => 'required|integer',
            'trade_period' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'symbol.required' => ':attribute不能为空',
            'currency.required' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
            'trade_period.required' => ':attribute不能为空',
        ];
    }

}
