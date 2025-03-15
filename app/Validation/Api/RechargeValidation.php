<?php

declare(strict_types=1);

namespace App\Validation\Api;

class RechargeValidation
{

    public static function attrs (): array
    {
        return[
            'coin' => '币种',
            'number' => '数量',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'coin' => 'required',
            'number' => 'required|integer',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'code.required' => ':attribute不能为空',
            'number.required' => ':attribute不能为空',
            'number.integer' => ':attribute只能整数',
        ];
    }

}
