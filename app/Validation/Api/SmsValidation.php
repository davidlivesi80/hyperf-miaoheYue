<?php

declare(strict_types=1);

namespace App\Validation\Api;

class SmsValidation
{

    public static function attrs (): array
    {
        return[
            'mobile' => '手机',
            'scene' => '场景',
            'area'  => '区号',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        //|regex:/^(\+(([0-9]){1,2})[-.])?((((([0-9]){2,3})[-.]){1,2}([0-9]{4,10}))|([0-9]{10}))$/
        //^\+[0-9]{1,3}\.[0-9]{4,14}(?:x.+)?$
        return [
            'mobile' => 'required|regex:/^\d{7,15}$/',  ///^1[3-8]{1}[0-9]{9}$/
            'scene' => 'required',
            'area'     => 'required|integer',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'mobile.required' => 'mobile_can_not_be_empty',
            'mobile.regex' => 'mobile_wrong_format',
            'scene.required' => 'scene_can_not_be_empty',
            'area.required' => 'area_can_not_be_empty',//区号不能为空
            'area.integer' => 'area_wrong_format',//区号格式错误
        ];
    }

}
