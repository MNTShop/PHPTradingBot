<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:01 AM
 */

namespace HotBitSDK\REST\Interfaces;

interface RESTRequestInterface
{

    public function getPath():string;
    public function getMethod():string;
    public function getParams():array;
    public function isNeedSign():bool;

}