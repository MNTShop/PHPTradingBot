<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:01 AM
 */

namespace HotBitSDK\WS\Interfaces;


interface WSResponseInterface
{
    public function getData();
    public function getCode();
    public function getRaw():string;
    public function getTopic():string;
    public function getTimestamp();
    public function getMessage():string;

    public function isError():bool;
    public function isAuthKeySuccess():bool;
    public function isSubscribed():bool;
    public function isConnected():bool;
    public function isUnSubscribed():bool;
    public function isInit():bool;
    public function isNormal():bool;

}