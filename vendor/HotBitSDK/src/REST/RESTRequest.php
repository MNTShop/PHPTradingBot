<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:10 AM
 */

namespace HotBitSDK\REST;

use HotBitSDK\REST\Interfaces\RESTRequestInterface;

abstract class RESTRequest implements RESTRequestInterface
{

    protected $method;

    protected $path;

    protected $isNeedSign;

    public function getMethod():string
    {
        return $this->method;
    }

    public abstract function getParams():array;

    public function getPath():string
    {
        return $this->path;
    }

    public function isNeedSign():bool
    {
        return $this->isNeedSign;
    }

}