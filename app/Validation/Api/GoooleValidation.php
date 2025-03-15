<?php

declare(strict_types=1);

namespace App\Validation\Api;

class GoooleValidation
{
    public static function attrs (): array
    {
        return[
            'paysword' => '钱包密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'paysword' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'paysword.required' => 'paysword_can_not_be_empty',//密码不能为空
        ];
    }

}
