<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class CrontabValidation
{
    public static function attrs (): array
    {
        return[
            'task_name' => '任务标识',
            'task_title' => '任务名称',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'task_name' => 'required',
            'task_title' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'task_name.required' => ':attribute不能为空',
            'task_title.required' => ':attribute不能为空',
        ];
    }

}
