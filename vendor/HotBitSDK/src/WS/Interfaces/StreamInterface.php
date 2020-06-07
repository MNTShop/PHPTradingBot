<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:01 AM
 */

namespace HotBitSDK\WS\Interfaces;


interface StreamInterface
{
    public function getPath():string;
    public function isNeedSign():bool;
}