<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:01 AM
 */

namespace HotBitSDK\REST\Interfaces;


interface RESTResponseInterface
{
    public function getData();
    public function getMessage():string;
    public function getCode();
    public function getRaw():string;
    public function isError():bool;

}