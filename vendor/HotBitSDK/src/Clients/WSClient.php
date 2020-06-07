<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 10:51 AM
 */

namespace HotBitSDK\Clients;

use HotBitSDK\WS\Interfaces\WSClientInterface;
use HotBitSDK\WS\WSResponse;
use HotBitSDK\WS\WSStream;


class WSClient extends AbstractClient implements WSClientInterface
{

    /**
     * Websocket stream endpoint
     * @var string
     */
    private $baseStream = 'wss://global-api.bithumb.pro/message/realtime';
    /**
     * View all websocket subscriptions
     * @var array
     */
    private $subscriptions = [];


    /**
     * terminate Terminates websocket endpoints. View endpoints first: print_r($api->subscriptions)
     * @param WSStream $stream
     */
    public function unSubscribe(WSStream $stream)
    {
        // check if $this->subscriptions[$endpoint] is true otherwise error
        $this->subscriptions[$this->baseStream.'?subscribe='.$stream->getPath()] = false;
        $headers = $this->generateSignatureHeader($stream);
        $endpoint = $this->baseStream.'?unSubscribe='.$stream->getPath();
        $this->subscriptions[$endpoint] = true;
        \Ratchet\Client\connect($endpoint,[],$headers)->then(function ($ws) use ($stream) {
            $ws->on('message', function ($data) use ($ws, $stream) {
                //provide data to stream
                $response = new WSResponse($data);
                call_user_func($stream->callback, $this, $stream, $response);
                return $ws->close();
            });
            $ws->on('close', function ($code = null, $reason = null) use ($ws, $stream) {
                // WPCS: XSS OK.
                $response = new WSResponse(json_encode([
                    'code' => 10006,
                    'msg' => $reason,
                ]));
                call_user_func($stream->callback, $this, $stream, $response);
                echo "ticker: WebSocket Connection closed! ({$code} - {$reason})" . PHP_EOL;
                return $ws->close();
            });
        }, function ($e) {
            // WPCS: XSS OK.
            echo "ticker: Could not connect: {$e->getMessage()}" . PHP_EOL;
        });
    }


    public function subscribe(WSStream $stream){
        $headers = $this->generateSignatureHeader($stream);
        $endpoint = $this->baseStream.'?subscribe='.$stream->getPath();
        $this->subscriptions[$endpoint] = true;
        \Ratchet\Client\connect($endpoint,[],$headers)->then(function ($ws) use ($stream, $endpoint) {
            $ws->on('message', function ($data) use ($ws, $stream, $endpoint) {
                if ($this->subscriptions[$endpoint] === false) {
                    $this->subscriptions[$endpoint] = null;
                     $ws->close();
                }
                $response = new WSResponse($data);
                if ($response->getCode() == 0) {
                    //need interact ping/pong
                    $this->ping();
                }else{
                    call_user_func($stream->callback, $this, $stream, $response);
                }

            });
            $ws->on('close', function ($code = null, $reason = null) use ($ws, $stream) {
                // WPCS: XSS OK.
                $response = new WSResponse(json_encode([
                'code' => 10006,
                'msg' => $reason,
                ]));
//                $this->unSubscribe($stream);
                call_user_func($stream->callback, $this, $stream, $response);
                $ws->close();
                echo "ticker: WebSocket Connection closed! ({$code} - {$reason})" . PHP_EOL;
            });
        }, function ($e) {
            // WPCS: XSS OK.
            echo "ticker: Could not connect: {$e->getMessage()}" . PHP_EOL;
        });
    }

    public function ping(){
        $endpoint = $this->baseStream.'?ping';
        \Ratchet\Client\connect($endpoint)->then(function ($ws) {
            $ws->on('message', function ($data) use ($ws) {
                $ws->close();
            });
            $ws->on('close', function ($code = null, $reason = null) use ($ws) {
                // WPCS: XSS OK.
                $ws->close();
            });
        }, function ($e) {
            // WPCS: XSS OK.
            echo "ping: Could not connect: {$e->getMessage()}" . PHP_EOL;
        });
    }



    /**
     * Generate signature string
     * @param WSStream $stream
     * @return array $paramsHeader
     */
    private function generateSignatureHeader(WSStream $stream)
    {
        $paramsHeader=[];
        if ($stream->isNeedSign()) {
            $signatureString = $this->baseStream . $stream->getPath() . $this->timestamp . $this->apiKey;
            $sign = hash_hmac("sha256", $signatureString, $this->secretKey, false);
            $paramsHeader = [
                "apiKey" => $this->apiKey,
                "apiTimestamp" => $this->timestamp,
                "apiSignature" => $sign
            ];
        }
        return $paramsHeader;
    }

//    private function readStream($streamData): WSResponse
//    {
//        return new WSResponse($streamData);
//    }
    //WS



//
//    /**
//     * tickerStreamHandler Convert WebSocket ticker data into array
//     *
//     * $data = $this->tickerStreamHandler( $json );
//     *
//     * @param $json object data to convert
//     * @return array
//     */
//    private function tickerStreamHandler(\stdClass $jsonData,$topic,$timestamp)
//    {
//        $ticker = [
//            "topic" => $topic,
//            "timestamp" => $timestamp,
//            "symbol" => $jsonData->symbol,
//            "percentChange" => $jsonData->p,
//            "high" => $jsonData->h,
//            "low" => $jsonData->l,
//            "numTrades" => $jsonData->v,
//            "close" => $jsonData->c,
//        ];
//
//        return $ticker;
//    }
//



}