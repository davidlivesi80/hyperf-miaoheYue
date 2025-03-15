<?php

declare(strict_types=1);

namespace App\Validation\Api;

class RegisEmailValidation
{

    public static function attrs (): array
    {
        return[
            'source'   => '来源',
            'method' => '方式',
            'email' => '邮箱',
            'password' => '密码',
            'password_confirmation' => '确认密码',
            'parent' =>'邀请人',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'source'   => 'required|in:app,web',
            'method'   => 'required|in:email,mobile',
            'email' => 'required|email|unique:user',
            'password' => 'required|different:email|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/|confirmed',
            'code'=>'required|digits:6',
            'parent' => 'filled|'//如果存在不能为空
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'source.required' => 'source_can_not_be_empty',//来源不能为空
            'source.in' => 'source_parameter_error',//来源参数错误
            'method.required' => 'method_can_not_be_empty',//方式不能为空
            'method.in' => 'method_parameter_error',//方式参数错误
            'email.required' => 'email_can_not_be_empty',//邮箱不能为空
            'email.unique' => 'email_alread_occupied',//邮箱已被占用
            'email.email' => 'email_wrong_format',//邮箱格式错误
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.different' => 'password_can_not_be_different',//密码不能与账号相同
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
            'password.confirmed' => 'password_wrong_confirmed',//密码和确认密码不一致
            'code.required' => 'email_can_not_be_empty',//手机不能为空
            'code.digits' => 'code_only_6_digits',//手机格式错误
            'parent.filled' => 'parent_can_not_be_empty',//邀请人不能为空

        ];
    }

}
