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
namespace App\Controller\Sys\System;

use App\Common\Service\System\SysConfigService;
use Upp\Basic\BaseController;
use Upp\Service\UploadService;
use Upp\Service\LoggerService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use App\Common\Service\System\SysFilesService;

class FilesController extends BaseController
{


    /**
     * @var SysFilesService
     */
    private  $logger;

    public function __construct(RequestInterface $request, ResponseInterface $response,ValidatorFactoryInterface $validator, SysFilesService $service)
    {
        parent::__construct($request,$response,$validator);
        $this->service = $service;
    }

    public function lists()
    {
        $where = $this->request->inputs(['cate','title']);

        $page = $this->request->input('page');

        $perPage = $this->request->input('limit');

        $lists  = $this->service->search($where,$page,$perPage);

        return $this->success('请求成功',$lists);

    }

    public function cate()
    {

        $lists  = $this->service->getAllCate();

        return $this->success('请求成功',$lists);

    }

    /**
     * 批量删除
     */
    public function batch(){

        $res = $this->service->batch($this->request->input('ids'));

        if(!$res){
            return $this->fail('操作失败');
        }

        return $this->success('操作成功');
    }

    public function uploadImage($id,$filed)
    {

        if ($id) {
            $cateIds = array_column($this->service->getAllCate(),'id');
            if (!in_array($id,$cateIds)) return $this->fail('目录不存在');
            $cateId = $id;
        } else {
            $cateId = 1;
        }
        $file = $this->request->file($filed);

        $this->validated(['image'=>$file],\App\Validation\Admin\UploadValidation::class);

        $res = $this->app(UploadService::class)->upload($file);

        if(!$res){
            return $this->fail('上传失败');
        }
        //添加素材数据
        $data = ['cate_id'=>$cateId, 'file_name'=>$res['filename'], 'file_src'=>$res['url'], 'upload_type'=>$res['drive']];
        $this->service->create(0,0,$data);
        return $this->success('上传成功',$data['file_src']);


    }

    public function uploadFiles(\Hyperf\Filesystem\FilesystemFactory $filesystem)
    {
        $file = $this->request->file('file');

        $this->validation(['image'=>$file],\App\Validation\UploadValidation::class);

        $extension = strtolower($file->getExtension()) ?: 'png';

        $filename = md5(uniqid(strval(mt_rand()), true)) . '.' . $extension;

        $qiniu = $filesystem->get('qiniu');

        $res = $qiniu->put($filename,file_get_contents($file->getRealPath()));

        if(!$res){
            return $this->fail('上传失败');
        }

        $url = env('QINBIU_DOMAIN').'/'.$filename;

        return $this->success('上传成功',$url);

    }


    public function logs()
    {

        $date = $this->request->input('date', date('Y-m-d'));

        $types = $this->request->input('types', 'error');

        $page =  (int)  $this->request->input('page', 1);

        $perPage =  (int) $this->request->input('limit', 10);

        $lists = $this->app(LoggerService::class)->paginator($page,$perPage,$date,$types);

        return $this->success('请求成功',$lists);
    }

    public function clear()
    {
        $date = $this->request->input('date', date('Y-m-d'));

        $types = $this->request->input('types', 'error');

        $this->app(LoggerService::class)->moveFileDate($types,$date);

        return $this->success('请求成功');
    }





}
