<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class PoolsValidation
{
    public static function attrs (): array
    {
        return[
            'total' => '投资金额',
            'username' => '用户名称',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'total' => 'required|integer',
            'username' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'total.required' => ':attribute不能为空',
            'total.integer' => ':attribute只能是整数',
            'username.required' => ':attribute不能为空',
        ];
    }

}
