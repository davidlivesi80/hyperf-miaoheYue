<?php

declare(strict_types=1);

namespace App\Validation\Api;

class RobotHashValidation
{
    public static function attrs(): array
    {
        return [
            'order_id' => '订单',
            'pay_hash' => '支付hash',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'order_id' => 'required|integer',
            'pay_hash' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'order_id.required' => ':attribute不能为空',
            'order_id.integer' => ':attribute只能整数',
            'pay_hash.required' => ':attribute不能为空',
        ];
    }

}
