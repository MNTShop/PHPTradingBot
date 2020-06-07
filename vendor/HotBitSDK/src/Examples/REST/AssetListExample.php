<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:44 AM
 */

namespace HotBitSDK\REST\Examples;

use HotBitSDK\RESTApplication;
use HotBitSDK\REST\Request\Spot\AssetListRequest;

class AssetListExample
{
    public static function sendRequest()
    {
        $timeData = ServerTimeExample::sendRequest();
        $timeData = json_decode($timeData,true);
        $timestamp = $timeData["timestamp"];

        $request = new AssetListRequest();
        $request->coinType = "BTC";
        $request->assetType = "spot";

        $apiKey = 'Your API Key';
        $secretKey = 'Your API SEcret';
        $msgNo = 'msgNo';
        $timestamp = $timeData["timestamp"];
        $client = new RESTApplication($apiKey, $secretKey, $msgNo, $timestamp);
        return $client->execute($request);
    }
}