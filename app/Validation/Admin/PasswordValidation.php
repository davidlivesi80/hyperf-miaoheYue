<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class PasswordValidation
{
    public static function attrs (): array
    {
        return[
            'oldPsw' => '旧密码',
            'newPsw' => '新密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'oldPsw' => 'required',
            'newPsw'  => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'oldPsw.required' => ':attribute不能为空',
            'newPsw.required' => ':attribute不能为空',
        ];
    }

}
