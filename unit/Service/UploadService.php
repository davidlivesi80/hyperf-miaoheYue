<?php
declare(strict_types=1);

namespace Upp\Service;

use App\Common\Service\System\SysConfigService;
use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\Filesystem;
use Upp\Traits\HelpTrait;
use Upp\Exceptions\AppException;

class UploadService
{

    use HelpTrait;

    function upload($file){

         $extension = strtolower($file->getExtension()) ?: 'png';
         $filename =  md5(uniqid(strval(mt_rand()), true)) . '.' . $extension;
         $filepath = 'upload'. DIRECTORY_SEPARATOR . $filename;
         $drive = $this->app(SysConfigService::class)->value('upload_drive');
         $filesystem =  $this->app(FilesystemFactory::class)->get($drive);
         try{
            $stream = fopen($file->getRealPath(), 'r+');
            $this->checkExecutable($stream);
            $filesystem->writeStream($filepath,$stream);
            if($file->getRealPath()){
                @unlink($file->getRealPath());
            }
            fclose($stream);
            if($drive == 'local'){
                $upload_url =  $this->app(SysConfigService::class)->value('upload_url');
                $url = $upload_url .DIRECTORY_SEPARATOR . $filepath;
            }else{
                $aws3_url =  $this->app(SysConfigService::class)->value('aws3_url');
                $url = $aws3_url .DIRECTORY_SEPARATOR. $filepath;
            }
            return compact('filename','url','drive');;

         } catch (\Throwable $e) {
             throw new AppException('上传失败'.$e->getMessage());
         }
    }

    protected function checkExecutable($stream)
    {
        $content = stream_get_contents($stream);
        //禁止上传PHP和HTML文件
        if (preg_match("/^php(.*)/i", $content)) {
            throw new AppException('非法上传',400);
        }
        return true;
    }

}