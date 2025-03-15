<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class SportOrderValidation
{
    public static function attrs (): array
    {
        return[
            'settle_content' => '赛事编码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'settle_content' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'settle_content.required' => ':attribute不能为空',
        ];
    }

}
