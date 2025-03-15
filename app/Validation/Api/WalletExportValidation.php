<?php


namespace App\Validation\Api;


class WalletExportValidation
{

    public static function attrs(): array
    {
        return [
            'paysword' => '密码'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'paysword' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'paysword.required' => 'paysword_can_not_be_empty',//密码不能为空
            'paysword.regex' => 'paysword_wrong_format',//密码格式错误,必须是字母或数字组合，至少6位
        ];
    }

}