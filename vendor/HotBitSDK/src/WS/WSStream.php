<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:10 AM
 */

namespace HotBitSDK\WS;
use HotBitSDK\WS\Interfaces\StreamInterface;

abstract class WSStream implements StreamInterface
{

    protected $path;

    /**
     * @var bool
     */
    protected $isNeedSign;

    public $callback;

    public $symbol;


    public function getPath():string
    {
        return $this->path;
    }

    public function isNeedSign():bool
    {
        return $this->isNeedSign;
    }

}