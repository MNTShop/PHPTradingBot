<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 4:04 PM
 */

$path = realpath(dirname(__FILE__));

require_once $path . "/vendor/autoload.php";

$data = \HotBitSDK\Example\AssetListExample::sendRequest();

var_dump(json_decode($data, true));