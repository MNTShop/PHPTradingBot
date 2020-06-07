<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\WS\Streams;

use HotBitSDK\WS\WSStream;

class OrderStream extends WSStream
{

    protected $path = "ORDER";
    protected $isNeedSign = true;
    public $callback;

    public function __construct(string $symbol, callable $callback)
    {
        $this->path .=':'.$symbol;
        $this->symbol=$symbol;
        $this->callback=$callback;
    }

}