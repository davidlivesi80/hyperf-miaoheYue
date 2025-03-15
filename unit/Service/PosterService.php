<?php
// +----------------------------------------------------------------------
// | 工具基于 GD 、ImageMagick 、PHPQRCode、wkhtmltopdf 🫡
// | PHP海报生成插件，极速生成方便快捷。
// | 快速生成海报、生成签到日、生成二维码、合成二维码、图片添加水印
// | 滑块验证图片生成、旋转验证图片生成、点击验证图片生成、输入验证图片生成
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace Upp\Service;

use Kkokk\Poster\PosterManager;
use Psr\SimpleCache\CacheInterface;
use Upp\Traits\HelpTrait;
use Upp\Exceptions\AppException;


class PosterService
{
    use HelpTrait;

    private $sliderParams =  [
        'src'           => '',  // 背景图片，尺寸 340 * 191
        'im_width'      => 340, // 画布宽度
        'im_height'     => 251, // 画布高度
        'im_type'       => 'png', // png 默认 jpg quality 质量
        'quality'       => 80,    // jpg quality 质量
        'bg_width'      => 340, // 背景宽度
        'bg_height'     => 191, // 背景高度
        'slider_width'  => 50,  // 滑块宽度
        'slider_height' => 50,  // 滑块高度
        'slider_border' => 2,   // 滑块边框
        'slider_bg'     => 1,   // 滑块背景数量
    ];

    private $clickParams =  [
        'src'         => '',
        'im_type'     => 'png', // png 默认 jpg quality 质量
        'quality'     => 80,    // jpg quality 质量
        'font_family' => '', // 感谢站酷提供免费商用站酷库黑体、可自定义炫酷字体文件（绝对路径）
        'contents'    => '', // 自定义文字
        'font_count'  => 0,  // 文字长度
        'font_size'   => 42, // 字体大小
        'line_count'  => 0,  // 干扰线数量
        'char_count'  => 0,  // 干扰字符数量
    ];

    private $inputParams = [
        'src'         => '',
        'im_width'    => 256,
        'im_height'   => 64,
        'im_type'     => 'png', // png 默认 jpg quality 质量
        'quality'     => 80,    // jpg quality 质量
        'type'        => 'math', // type = number 数字 alpha_num 字母和数字 math 计算 text 文字
    ];

    private $rotateParams = [
        'src'         => '',
        'im_width'    => 250,
        'im_height'   => 250,
        'im_type'     => 'png', // png 默认 jpg quality 质量
        'quality'     => 80,    // jpg quality 质量
    ];


    //滑块验证-生产
    public function getSliderCaptcha()
    {
        /**
         * 获取滑块验证图片
         * @return array 返回格式如下
         * img 是base64格式的图片
         * key 是验证时需要使用的值
         * y 是前端渲染滑块的高度
         * secret 是正确的密钥（在没有内置缓存的情况下会返回）
         */
        $result = PosterManager::Captcha()->type('slider')->config($this->sliderParams)->get();

        return $result;
    }

    //滑块验证-验证
    public function checkSliderCaptcha($key,$value,$secret)
    {
        /**
         * 滑块验证
         * @param string $key
         * @param string $value 滑动位置的值
         * @param int $leeway 允许的误差值
         * @param int $secret 返回的密钥（如果前面没返回这个参数，这里就不用传）
         * @return bool true 验证成功 false 失败
         */
        $result = PosterManager::Captcha()->type('slider')->check($key, $value, 5,$secret);
        $captchaKey = 'hukuai_token' .$key;
        $sign = '';
        if($result){
            $sign = md5($key.$value .'Unit2084#');
            $this->app(CacheInterface::class)->set($captchaKey, $sign, 120);
        }
        return ['captchaKey'=>$captchaKey,'captchaSign'=>$sign];
    }

    //点选验证-生产
    public function getClickCaptcha()
    {
        /**
         * 获取滑块验证图片
         * @return array 返回格式如下
         * img 是base64格式的图片
         * key 是验证时需要使用的值
         * y 是前端渲染滑块的高度
         * secret 是正确的密钥（在没有内置缓存的情况下会返回）
         */
        $result = PosterManager::Captcha()->type('click')->config($this->clickParams)->get();

        return $result;
    }

    //点选验证-验证
    public function checkClickCaptcha($key,$value)
    {
        /**
         * 滑块验证
         * @param string $key
         * @param string $value 滑动位置的值
         * @param int $leeway 允许的误差值
         * @param int $secret 返回的密钥（如果前面没返回这个参数，这里就不用传）
         * @return bool true 验证成功 false 失败
         */
        $result = PosterManager::Captcha()->type('click')->check($key, $value);
        if(!$result ){
            return false;
        }
        return true;
    }

    //输入验证-生产
    public function getInputCaptcha()
    {
        /**
         * 获取滑块验证图片
         * @return array 返回格式如下
         * img 是base64格式的图片
         * key 是验证时需要使用的值
         * y 是前端渲染滑块的高度
         * secret 是正确的密钥（在没有内置缓存的情况下会返回）
         */
        $result = PosterManager::Captcha()->type('input')->config($this->inputParams)->get();
        if($result){
            $this->app(CacheInterface::class)->set($result['key'],$result['secret'],60);
        }
        return ['key'=>$result['key'],'img'=>$result['img']] ;
    }

    //输入验证-验证
    public function checkInputCaptcha($key,$value)
    {
        /**
         * 滑块验证
         * @param string $key
         * @param string $value 滑动位置的值
         * @return bool true 验证成功 false 失败
         */
        $result = $this->app(CacheInterface::class)->get($key);
        if($result != $value){
            return false;
        }
        $this->app(CacheInterface::class)->delete($key);
        return true;
    }

    //旋转验证-生产
    public function getRotateCaptcha()
    {
        /**
         * 获取验证参数
         * 内部使用了 laravel 的 cache 缓存，返回的是图片的 base64 、 缓存key
         * @param string $type   验证码类型
         * @param array  $params 验证码自定义参数
         * @return arary
         */
        $result = PosterManager::Captcha()->type('rotate')->config($this->rotateParams)->get();
        if($result){
            $this->app(CacheInterface::class)->set($result['key'],$result['secret'],60);
        }
        return ['key'=>$result['key'],'img'=>$result['img']] ;
    }

    //旋转验证-验证
    public function checkRotateCaptcha($key,$value)
    {
        /**
         * 验证
         * 前端根据相关滑块操作进行处理, 返回x坐标，返回 true 则验证成功
         * @param string     $key     缓存key
         * @param string|int $value   前端传回来的旋转角度
         * @param int        $leeway  误差值
         * @return boolean
         */
        $result = $this->app(CacheInterface::class)->get($key);
        $min_value = bcsub((string)$result,'5');
        $max_value = bcadd((string)$result,'5');
        if( $value < $min_value || $value > $max_value){
            return false;
        }
        $this->app(CacheInterface::class)->delete($key);
        return true;
    }







}