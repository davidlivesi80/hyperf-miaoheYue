<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class CardAttrValueValidation
{
    public static function attrs (): array
    {
        return[
            'attr_value' => '属性值',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'attr_value' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'attr_value.required' => ':attribute不能为空',
        ];
    }

}
