<?php
namespace Upp\Basic;

use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;


class BaseService
{
    use HelpTrait;

    use RedisTrait;

    /**
     * @var $logic
     */
    protected $logic;

    public function setLogic($logic){
        $this->logic = $logic;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->logic, $name], $arguments);
    }
}