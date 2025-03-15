<?php
declare(strict_types=1);

namespace Upp\Service\Upload\Storage;

use Hyperf\Filesystem\Contract\AdapterFactoryInterface;
use League\Flysystem\AdapterInterface;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

class Qiniu implements AdapterFactoryInterface
{
    use HelpTrait;
    public function make(array $options)
    {
        $options['accessKey'] =  $this->app(SysConfigService::class)->value('qiniu_access_key');
        $options['secretKey'] =  $this->app(SysConfigService::class)->value('qiniu_secret_key');;
        $options['bucket'] =  $this->app(SysConfigService::class)->value('qiniu_bucket');;
        $options['domain'] =  $this->app(SysConfigService::class)->value('qiniu_domain');;

        return new QiniuAdapter($options['accessKey'], $options['secretKey'], $options['bucket'], $options['domain']);
    }
}