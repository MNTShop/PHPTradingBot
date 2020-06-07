<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\REST\Examples;

use HotBitSDK\Clients\RESTClient;
use HotBitSDK\Examples\REST\ServerTimeExample;
use HotBitSDK\Objects\Order;
use HotBitSDK\REST\Request\Spot\OrderListRequest;

class OrderListExample
{
    public static function sendRequest()
    {

        $apiKey = 'Your API Key';
        $secretKey = 'Your API SEcret';
        $msgNo = '1234567890';

        $timeData = ServerTimeExample::getTimestamp();
        $timeData = json_decode($timeData,true);
        $timestamp = $timeData["timestamp"];
        $client = new RESTClient($apiKey, $secretKey, $msgNo, $timestamp);
        //last 10
        if($client->getResponse(new OrderListRequest(
            'buy',
            'BIP-USDT',
            'pending',
            'thisweek',
            0,
            10)
        )->isError()){
            error_log('Code: '.$client->response->getCode().PHP_EOL.'Message: '.$client->response->getMessage());
            return false;
        }else{
            return $client->response->getData();
        }

    }
}