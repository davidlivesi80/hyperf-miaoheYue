<?php


namespace App\Validation\Api;


class WalletNotifyValidation
{

    public static function attrs (): array
    {
        return[
            'recharge_id' =>'订单ID',
            'status' =>'状态装',
            'types' =>'订单类型',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'recharge_id' => 'required',
            'status' => 'required',
            'types' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'recharge_id.required' => ':attribute不能为空',
            'status.status' => ':attribute不能为空',
            'types.status' => ':attribute不能为空',
        ];
    }

}