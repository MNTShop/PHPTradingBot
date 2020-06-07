<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\REST\Examples;

use HotBitSDK\Examples\REST\ServerTimeExample;
use HotBitSDK\REST\Request\Spot\CancelOrdersRequest;

class CancelOrdersExample
{
    public static function sendRequest()
    {
        $timestamp =  (int)(ServerTimeExample::sendRequest()/100);
        error_log($timestamp);
        $ordersIDSArr = ['74740189190115328','74740189190115330','74740189190115330'];
        $request = new CancelOrdersRequest($ordersIDSArr,'BIP-USDT');
        $client = new RESTApplication();
        if($client->getResponse($request)->isError()){
            error_log('Code: '.$client->response->getCode().PHP_EOL.'Message: '.$client->response->getMessage());
            return false;
        }else{
            return $client->response->getData();
        }
    }
}