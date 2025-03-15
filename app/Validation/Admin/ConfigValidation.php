<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ConfigValidation
{
    public static function attrs (): array
    {
        return[
            'name' => '配置名',
            'key' => '配置项',
            'types' => '配置分组',
            'element' => '配置类型',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'name' => 'required',
            'key' => 'required',
            'types' => 'required',
            'element' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'name.required' => ':attribute不能为空',
            'key.required' => ':attribute不能为空',
            'types.required' => ':attribute不能为空',
            'element.required' => ':attribute不能为空',
        ];
    }

}
