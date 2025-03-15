<?php
declare(strict_types=1);

namespace Upp\Service;

use Hyperf\Utils\Collection;

class LoggerService
{

    //hyperf_job-2021-03-21.log
    protected $root = BASE_PATH . "/runtime/logs/";

    protected $suffix = ".log";

    public function getLogFiles($filename='')
    {
        $lineList = [];

        $fullPath = $this->root . $filename . $this->suffix;

        if (file_exists($fullPath)) {
            $content    = $this->readFileLine($fullPath);
            foreach ($content as $value){
                $lineOne['data'] = $value;
                $lineList[] = $lineOne;
            }
        }

        return $lineList;
    }

    public function paginator($page,$perPage,$date,$types='error')
    {
        $filename = $types.'-'.$date;
        $lineList = $this->getLogFiles($filename,$types);
        // 这里根据 $currentPage 和 $perPage 进行数据查询，以下使用 Collection 代替
        $collection = (new Collection($lineList))->sortByDesc("date")->values();
        $lists = array_values($collection->forPage($page, $perPage)->toArray());
        $option = [
            'data' =>$lists,
            'page' => $page,
            'perPage'=>$perPage,
            'total'=>$collection->count(),
        ];
        return $option;
    }

    /**
     * @param $fullPath
     * @return array
     */

    private function readFileLine($fullPath) {
        $content = [];
        //读写方式打开，将文件指针指向文件头
        $handle  = fopen($fullPath, "r+");
        if (is_resource($handle)) {
            while (feof($handle) == false) {
                $line = fgets($handle);
                if ($line) {
                    $content[] = $line;
                }
            }
        }
        return $content;
    }

    public function moveFileDate($types='error',$date='')
    {
        $fullPath = $this->root . $types.'-'.$date . $this->suffix;

        if (file_exists($fullPath)) {
            file_put_contents($fullPath,'');
        }

    }

}