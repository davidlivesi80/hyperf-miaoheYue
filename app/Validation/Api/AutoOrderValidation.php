<?php

declare(strict_types=1);

namespace App\Validation\Api;

class AutoOrderValidation
{
    public static function attrs (): array
    {
        return[
            'id'=>'订单',
            'paysword' => '支付密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'id' => 'required|integer',
            'paysword' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'id.required' => 'id_can_not_be_empty',//订单ID不能为空
            'id.integer' => 'id_only_numbers',//订单ID只能数字
            'paysword.required' => 'paysword_can_not_be_empty',//密码不能为空
        ];
    }

}
