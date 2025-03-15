<?php


namespace App\Validation\Api;


class WalletImportValidation
{

    public static function attrs(): array
    {
        return [
            'source' => "来源",
            'mnemonic' => '助记词',
            'series' => '通道',
            'password'=>"密码"
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'source'   => 'required|in:app,web',
            'mnemonic' => 'required',
            'series' => 'required|in:1',
            'password' => 'required|different:mnemonic|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'source.required' => 'source_can_not_be_empty',//来源不能为空
            'source.in' => 'source_parameter_error',//来源参数错误
            'mnemonic.required' => 'mnemonic_can_not_be_empty',//助记词不能为空
            'series.required' => 'series_can_not_be_empty',//通道不能为空
            'series.in' => 'series_parameter_error',//通道格式错误
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.different' => 'password_can_not_be_different',//密码不能与账号相同
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
        ];
    }

}