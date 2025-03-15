<?php

declare(strict_types=1);

namespace App\Validation\Api;

class CardCompoundValidation
{
    public static function attrs(): array
    {
        return [
            'card_ids'=>'卡片ID',
            'paysword' => '支付密码'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'card_ids' => 'required',
            'paysword' => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'card_ids.required' => 'card_can_not_be_empty',//卡片不能为空
            'paysword.required' => 'paysword_can_not_be_empty'//钱包密码不能为空
        ];
    }

}
