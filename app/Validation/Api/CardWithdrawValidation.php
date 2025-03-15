<?php

declare(strict_types=1);

namespace App\Validation\Api;

class CardWithdrawValidation
{
    public static function attrs(): array
    {
        return [
            'card_id'=>'卡片ID',
            'number' => '数量',
            'paysword' => '支付密码'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'card_id' => 'required|integer',
            'number' => 'required|numeric|gt:0',
            'paysword' => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'card_id.required' => 'card_can_not_be_empty',//卡片不能为空
            'card_id.integer' => 'card_parameter_error',//卡片参数错误
            'number.required' => 'number_can_not_be_empty',//数量不能为空
            'number.numeric' => 'number_only_numbers',//数量只能数值
            'number.gt' => 'number_must_gt_zero',//金额必须大于0
            'paysword.required' => 'paysword_can_not_be_empty'//钱包密码不能为空
        ];
    }

}
