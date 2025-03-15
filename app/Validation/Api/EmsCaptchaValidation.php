<?php

declare(strict_types=1);

namespace App\Validation\Api;

class EmsCaptchaValidation
{

    public static function attrs(): array
    {
        return [
            'captchaKey' => '滑块Key',
            'captchaSign' => '滑块Sign',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'captchaKey' => 'required',
            'captchaSign' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'captchaKey.required' => 'captchaKey_can_not_be_empty',//滑块Key不能为空
            'captchaSign.required' => 'captchaSign_can_not_be_empty',//滑块Sign不能为空
        ];
    }

}
