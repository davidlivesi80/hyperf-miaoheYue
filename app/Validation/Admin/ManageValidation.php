<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ManageValidation
{
    public static function attrs (): array
    {
        return[
            'manage_name' => '账号',
            'roleIds' => '角色',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'manage_name' => 'required',

            'roleIds'  => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'manage_name.required' => ':attribute不能为空',
            'roleIds.required' => ':attribute不能为空',
        ];
    }

}
