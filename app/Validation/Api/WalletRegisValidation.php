<?php

declare(strict_types=1);

namespace App\Validation\Api;

class WalletRegisValidation
{

    public static function attrs (): array
    {
        return[
            'parent' => "邀请ID",
            'password' => '密码',
            'password_confirmation' => '确认密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'parent' => 'required|exists:user,username',
            'password' => 'required|different:parent|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/|confirmed',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'parent.required' => 'parent_can_not_be_empty',//邀请ID不能为空
            'parent.exists' => 'parent_exists',//邀请ID错误
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.different' => 'password_can_not_be_different',//密码不能与账号相同
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
            'password.confirmed' => 'password_wrong_confirmed',//密码和确认密码不一致
        ];
    }

}
