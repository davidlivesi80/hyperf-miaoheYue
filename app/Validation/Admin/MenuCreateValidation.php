<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class MenuCreateValidation
{
    public static function attrs (): array
    {
        return[
            'title' => '菜单名称',
            'parentId' => '父级菜单',
            'menuType' => '类型',
            'openType' => '方式',
            'sort' => '排序',
            'hide' => '显隐',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'title' => 'required',
            'parentId' => 'required|integer',
            'menuType' => 'required|integer|in:0,1',
            'openType' => 'required|integer|in:0,1,2',  
            'sort' => 'required|integer',
            'hide' => 'required|integer|in:0,1',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'title.required' => ':attribute不能为空',
            'parentId.required' => ':attribute不能为空',
            'parentId.integer' => ':attribute只能整数',
            'menuType.required' => ':attribute不能为空',
            'menuType.integer' => ':attribute只能整数',
            'menuType.in' => ':attribute数值非法',
            'openType.required' => ':attribute不能为空',
            'openType.integer' => ':attribute只能整数',
            'openType.in' => ':attribute数值非法',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能整数',
            'hide.required' => ':attribute不能为空',
            'hide.integer' => ':attribute只能整数',
            'hide.in' => ':attribute数值非法',
        ];
    }

}
