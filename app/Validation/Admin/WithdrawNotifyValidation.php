<?php

namespace App\Validation\Admin;


class WithdrawNotifyValidation
{

    public static function attrs(): array
    {
        return [
            'id' => '订单',
            'hash' => '哈希',
            'status' => '状态',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'id' => 'required',
            'hash' => 'required',
            'status' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'id.required' => ':attribute不能为空',
            'hash.required' => ':attribute不能为空',
            'status.required' => ':attribute不能为空',
        ];
    }

}