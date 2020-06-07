<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\Examples\REST;

use HotBitSDK\Clients\RESTClient;
use HotBitSDK\REST\Request\ServerTimeRequest;

class ServerTimeExample
{
    public static function getTimestamp()
    {
        $request = new ServerTimeRequest();
        $client = new RESTClient();
        if($client->getResponse($request)->isError()){
            error_log('Code: '.$client->response->getCode().PHP_EOL.'Message: '.$client->response->getMessage());
            return false;
        }else{
            return $client->response->getData();
        }
    }
}