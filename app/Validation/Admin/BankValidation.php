<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class BankValidation
{

    public static function attrs (): array
    {
        return[
            'bank_type' => '通道',
            'bank_account'=>'地址',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'bank_type' => 'required|in:3,4,5,6',
            'bank_account' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'bank_type.required' => ':attribute不能为空',//
            'bank_type.in' => ':attribute通道参数错误',//通道参数错误
            'bank_account.required' => ':attribute不能为空',//不能为空
        ];
    }

}
