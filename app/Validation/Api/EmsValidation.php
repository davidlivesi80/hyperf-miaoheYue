<?php

declare(strict_types=1);

namespace App\Validation\Api;

class EmsValidation
{

    public static function attrs (): array
    {
        return[
            'email' => '邮箱',
            'scene' => '场景',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'email' => 'required|email',
            'scene' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'email.required' => 'email_can_not_be_empty',//邮箱不能为空
            'email.email' => 'email_wrong_format',//邮箱格式错误
            'scene.required' => 'scene_can_not_be_empty',//场景不能为空
        ];
    }

}
