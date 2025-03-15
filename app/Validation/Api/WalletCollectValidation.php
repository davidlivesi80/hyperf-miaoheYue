<?php


namespace App\Validation\Api;


class WalletCollectValidation
{

    public static function attrs (): array
    {
        return[
            'recharge_id' =>'订单ID',
            'is_collect' =>'状态装',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'recharge_id' => 'required',
            'is_collect' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'recharge_id.required' => ':attribute不能为空',
            'is_collect.status' => ':attribute不能为空',
        ];
    }

}