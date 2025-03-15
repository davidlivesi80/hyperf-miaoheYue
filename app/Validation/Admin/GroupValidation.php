<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class GroupValidation
{
    public static function attrs (): array
    {
        return[
            'group_name' => '角色名称',
            'group_code' => '角色标识',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'group_name' => 'required',
            'group_code' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'group_name.required' => ':attribute不能为空',
            'group_code.required' => ':attribute不能为空',
        ];
    }

}
