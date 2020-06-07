<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\WS\Examples;

use HotBitSDK\HotBitClient;
use HotBitSDK\WS\Interfaces\WSClientInterface;
use HotBitSDK\WS\Streams\TickerStream;
use HotBitSDK\WS\WSResponse;

class TopicTickerExample
{
    public static function Test()
    {
        $client = new HotBitClient();
        $client->subscribe(new TickerStream(
            'BIP-USDT',
            function (WSClientInterface $client,TickerStream $stream ,WSResponse $response) {
                if ($response->isError()) {
                    error_log(print_r($response,1));
                    $response->getCode(); //code of error
                    $response->getRaw(); //raw response
                    $client->unSubscribe($stream); //unsubscribe now and free space
                }
                if ($response->isNormal()) {
                    error_log(print_r( $response->getData(),1));
                    $response->getData(); //get all data
                    $response->getTopic(); //topic
                    $response->getTimestamp(); //timestamp
                    $client->unSubscribe($stream);
                }
            }));
    }

}