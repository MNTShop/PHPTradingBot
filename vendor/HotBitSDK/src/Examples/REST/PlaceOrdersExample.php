<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\REST\Examples;

use HotBitSDK\RESTApplication;
use HotBitSDK\Objects\Order;
use HotBitSDK\REST\Request\Spot\PlaceOrdersRequest;

class PlaceOrdersExample
{
    public static function sendRequest()
    {

        $apiKey = 'Your API Key';
        $secretKey = 'Your API SEcret';
        $msgNo = '1234567890';

        $timestamp = ServerTimeExample::sendRequest();
        $client = new RESTApplication($apiKey, $secretKey, $msgNo, $timestamp);
        if($client->getResponse(new PlaceOrdersRequest(
            [   //MAX 10 orders in batch
                new Order(
                [
                    'timestamp'=>$timestamp,
                    'type'=>'market',
                    'symbol' => 'BIP-USDT',
                    'side'=>'buy',
                    'price'=>'-1',
                    'quantity'=>'700'
                ]),new Order(
                [
                    'timestamp'=>$timestamp,
                    'type'=>'market',
                    'symbol' => 'BIP-USDT',
                    'side'=>'buy',
                    'price'=>'-1',
                    'quantity'=>'700'
                ]),new Order(
                [
                    'timestamp'=>$timestamp,
                    'type'=>'market',
                    'symbol' => 'BIP-USDT',
                    'side'=>'buy',
                    'price'=>'-1',
                    'quantity'=>'700'
                ]),new Order(
                [
                    'timestamp'=>$timestamp,
                    'type'=>'market',
                    'symbol' => 'BIP-USDT',
                    'side'=>'buy',
                    'price'=>'-1',
                    'quantity'=>'700'
                ]),
            ]
        ))
            ->isError()){
            error_log('Code: '.$client->response->getCode().PHP_EOL.'Message: '.$client->response->getMessage());
            return false;
        }else{
            return $client->response->getData();
        }
        // or Like that
//        $order = new Order();
//        $order->timestamp = $timestamp;
//        $order->type = 'market';
//        $order->symbol = 'BIP-USDT';
//        $order->side = 'buy';
//        $order->price = '-1';
//        $order->quantity = '700';
//
//        $request = new PlaceOrdersRequest([$order]);
//        $client = new RESTApplication($apiKey, $secretKey, $msgNo, $timestamp);
//        $response = $client->getResponse($request);
//
//        if($response->isError()){
//            error_log('Code: '.$response->getCode().PHP_EOL.'Message: '.$response->getMessage());
//            return false;
//        }
//        return $response->getData();
    }
}