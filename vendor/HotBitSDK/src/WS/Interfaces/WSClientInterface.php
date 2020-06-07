<?php
/**
 * Created by PhpStorm.
 * User: devacc
 * Date: 26.05.2020
 * Time: 16:53
 */

namespace HotBitSDK\WS\Interfaces;

use HotBitSDK\WS\WSStream;

interface WSClientInterface
{
    public function subscribe(WSStream $stream);
    public function unSubscribe(WSStream $stream);
}