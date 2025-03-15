<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class WalletValidation
{

    public static function attrs (): array
    {
        return[
            'coin' => '币种',
            'number' => '数量'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'coin' => 'required',
            'number' => 'required|numeric|notIn:0',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'coin.required' => ':attribute不能为空',
            'number.required' => ':attribute不能为空',
            'number.numeric' => ':attribute只能数字',
            'number.notIn' => ':attribute不能为0',
        ];
    }

}
