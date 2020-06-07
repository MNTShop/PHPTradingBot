<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\REST\Examples;

use HotBitSDK\RESTApplication;
use HotBitSDK\REST\Request\Spot\TickerRequest;

class SpotTickerExample
{
    public static function sendRequest()
    {
        $request = new TickerRequest();
        $client = new RESTApplication();
        if($client->getResponse($request)->isError()){
            error_log('Code: '.$client->response->getCode().PHP_EOL.'Message: '.$client->response->getMessage());
            return false;
        }else{
            return $client->response->getData();
        }
    }
}