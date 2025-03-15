<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ValuedValidation
{
    public static function attrs (): array
    {
        return[
            'value' => '配置值',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'value' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'value.required' => ':attribute不能为空',
        ];
    }

}
