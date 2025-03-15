<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Upp\Basic;

use Upp\Exceptions\AppException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Upp\Traits\HelpTrait;


abstract class BaseController
{
    use HelpTrait;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var ValidatorFactoryInterface
     */
    protected $validator;

    // 通过在构造函数的参数上声明参数类型完成自动注入
    public function __construct(RequestInterface $request,ResponseInterface $response,ValidatorFactoryInterface $validator)
    {
        $this->request = $request;
        $this->response = $response;
        $this->validator = $validator;
    }

    /**
     * 请求成功
     *
     * @param array $data
     * @param string $message
     * @return array
     */
    protected function success($message, $data = [])
    {
        return [ 'code' => 0, 'message' => $message, 'data' => $data];
    }

    /**
     * 请求失败
     * @param array  $data
     * @param string $message
     * @return array
     */
    protected function fail($message,$code = 400)
    {
        return [ 'code' => $code, 'message' => $message];
    }

    protected function validated($data=[],$validated)
    {
        //排除空数据
        $data = array_filter($data,function ($item) {
            if($item == 0){
                return true;
            }
            if($item == ''){
                return true;
            }

            if($item == null){
                return false;
            }
            return $item;
        });
        $validator = $this->validator->make($data,$validated::rules(),$validated::messages(),$validated::attrs());
        if ($validator->fails()){
            throw new AppException($validator->errors()->first(),400);
        }
    }

}
