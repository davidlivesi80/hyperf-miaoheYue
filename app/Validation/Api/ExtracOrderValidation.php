<?php

declare(strict_types=1);

namespace App\Validation\Api;

class ExtracOrderValidation
{

    public static function attrs (): array
    {
        return[
            'code' => '验证码',
            'order_id' => '订单',
            'addr_id' => '地址',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'code' => 'required|digits:4',
            'order_id' => 'required|integer',
            'addr_id' => 'required|integer',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'code.required' => ':attribute不能为空',
            'code.digits' => ':attribute只能4位数字',
            'order_id.required' => ':attribute不能为空',
            'order_id.integer' => ':attribute只能整数',
            'addr_id.required' => ':attribute不能为空',
            'addr_id.integer' => ':attribute只能整数',
        ];
    }

}
