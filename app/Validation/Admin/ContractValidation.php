<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ContractValidation
{
    public static function attrs (): array
    {
        return[
            'contract_title' => '合约名称',
            'contract_name'  => '合约标识',
            'contract_address' => '合约地址',
            'contract_abi' => '合约ABI'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'contract_title' => 'required',
            'contract_name' => 'required',
            'contract_address' => 'required',
            'contract_abi' => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'contract_title.required' => ':attribute不能为空',
            'contract_name.required' => ':attribute不能为空',
            'contract_address.required' => ':attribute不能为空',
            'contract_abi.required' => ':attribute不能为空'
        ];
    }

}
