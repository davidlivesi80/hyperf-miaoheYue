<?php

declare(strict_types=1);

namespace App\Validation\Api;

class ForgetMobileValidation
{

    public static function attrs (): array
    {
        return[
            'method' => '方式',
            'area'     => '区号',
            'mobile' => '手机',
            'password' => '密码',
            'password_confirmation' => '确认密码',
            'code' =>'验证码',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'method'   => 'required|in:email,mobile',
            'area'     => 'required|integer',
            'mobile' => 'required|regex:/^\d{7,15}$/',
            'password' => 'required|different:email|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/|confirmed',
            'code' => 'required|digits:6',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'method.required' => 'method_can_not_be_empty',//方式不能为空
            'method.in' => 'method_parameter_error',//方式参数错误
            'area.required' => 'area_can_not_be_empty',//区号不能为空
            'area.integer' => 'area_wrong_format',//区号格式错误
            'mobile.required' => 'mobile_can_not_be_empty',//手机不能为空
            'mobile.regex' => 'mobile_wrong_format',//手机格式错误
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.different' => 'password_can_not_be_different',//密码不能与账号相同
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
            'password.confirmed' => 'password_wrong_confirmed',//密码和确认密码不一致
            'code.required' => 'code_can_not_be_empty',//验证码不能为空,
            'code.digits' => 'code_only_6_digits'//验证码格式错误,
        ];
    }

}
