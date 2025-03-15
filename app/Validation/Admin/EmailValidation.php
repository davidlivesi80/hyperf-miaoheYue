<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class EmailValidation
{
    public static function attrs (): array
    {
        return[
            'host' => '主机',
            'port' => '端口',
            'username' => '账号',
            'password' => '密码',
            'encryption' => '方式',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'host' => 'required',
            'port' => 'required',
            'username' => 'required',
            'password' => 'required',
            'encryption' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'host.required' => ':attribute不能为空',
            'port.required' => ':attribute不能为空',
            'username.required' => ':attribute不能为空',
            'password.required' => ':attribute不能为空',
            'encryption.required' => ':attribute不能为空',

        ];
    }

}
