<?php
/**
 * Created by PhpStorm.
 * User: devacc
 * Date: 26.05.2020
 * Time: 16:21
 */

namespace HotBitSDK;

use HotBitSDK\Clients\RESTClient;
use HotBitSDK\Clients\WSClient;
use HotBitSDK\Examples\REST\ServerTimeExample;
use HotBitSDK\REST\Interfaces\RESTClientInterface;
use HotBitSDK\REST\Interfaces\RESTRequestInterface;
use HotBitSDK\REST\RESTResponse;
use HotBitSDK\WS\Interfaces\WSClientInterface;
use HotBitSDK\WS\WSStream;

class HotBitClient implements WSClientInterface,RESTClientInterface
{

    /**
     * Rest Client container
     * @var
     */
    protected $RESTClient;

    /**
     * WS Client container
     * @var
     */
    protected $WSClient;

    /**
     * @var RESTResponse
     */
    public $response;

    /**
     * @var RESTRequestInterface
     */
    public $request;

    public function __construct(string $apiKey = '', string $apiSecretKey= '', string $msgNo = '1234567890')
    {
        $BithumbTime = ServerTimeExample::getTimestamp();
        $this->RESTClient = new RESTClient($apiKey,$apiSecretKey,$BithumbTime,$msgNo);
        $this->WSClient = new WSClient($apiKey,$apiSecretKey,$BithumbTime);
    }
    public function subscribe(WSStream $stream)
    {
        return $this->WSClient->subscribe($stream);
    }
    public function unSubscribe(WSStream $stream)
    {
        return $this->WSClient->unSubscribe($stream);
    }
    public function getResponse(RESTRequestInterface $request): RESTResponse
    {
        $this->response = $this->RESTClient->getResponse($request);
        return $this->response;
    }
    public function getRequest(): RESTRequestInterface
    {
        $this->request = $this->RESTClient->getRequest();
        return $this->request;
    }
}