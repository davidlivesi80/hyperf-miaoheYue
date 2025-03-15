<?php
declare(strict_types=1);

namespace Upp\Service\Upload\Storage;

use Aws\Handler\GuzzleV6\GuzzleHandler;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Filesystem\Contract\AdapterFactoryInterface;
use Hyperf\Filesystem\Version;
use Hyperf\Guzzle\CoroutineHandler;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

class Aws3 implements AdapterFactoryInterface
{
    use HelpTrait;

    public function make(array $options)
    {
        $options['credentials'] =[
            'key' => $this->app(SysConfigService::class)->value('aws3_key_id'),
            'secret' => $this->app(SysConfigService::class)->value('aws3_access_key'),
        ];
        $options['region'] = $this->app(SysConfigService::class)->value('aws3_region');
        $options['version'] = 'latest';
        $options['bucket_endpoint'] = false;
        $options['use_path_style_endpoint'] = false;
        $options['endpoint'] = NULL;
        /**
         * 此开发工具包安装使用 PHP 版本 7.4.33，该版本将于 2025 年 1 月 13 日弃用。请将您的 PHP 版本至少升级到 8.1.x，以继续接收适用于 PHP 的 AWS 开发工具包的更新。
         * 要禁用此警告，请在客户端构造函数中将suppress_php_deprecation_warning 设置为true，或将环境变量
        */
        $options['suppress_php_deprecation_warning'] = true;
        $options['bucket_name'] = $this->app(SysConfigService::class)->value('aws3_bucket');
        $options['ACL'] = 'public-read';
        $handler = new GuzzleHandler(new Client([
            'handler' => HandlerStack::create(new CoroutineHandler()),
        ]));
        $options = array_merge($options, ['http_handler' => $handler]);
        $client = new S3Client($options);
        if (Version::isV2()) {
            return new AwsS3V3Adapter($client, $options['bucket_name']);
        }
        return new AwsS3Adapter($client, $options['bucket_name'],'' , ['override_visibility_on_copy' => true]);
    }
}