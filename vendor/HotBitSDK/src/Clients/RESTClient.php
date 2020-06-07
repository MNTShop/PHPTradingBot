<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 10:51 AM
 */

namespace HotBitSDK\Clients;

use HotBitSDK\REST\Interfaces\RESTClientInterface;
use HotBitSDK\REST\Interfaces\RESTRequestInterface;
use HotBitSDK\REST\RESTRequest;
use HotBitSDK\REST\RESTResponse;
use HotBitSDK\Utils\CurlUtil;

class RESTClient extends AbstractClient implements RESTClientInterface
{
    /**
     * REST API endpoint
     * @var string
     */
    private $baseEndpoint = "https://api.hotbit.io/api/v1";

    /**
     * just for some api(place order),max length is 50 chars
     * @var string
     */
    private $msgNo;

    /**
     * include some rest api(virtual coin,contracts) and error code list.
     * @var string
     */
    private $version = 'V1.0.0';

    /**
     * Response object
     * @var RESTResponse
     */
    public $response;
    /**
     * Request object
     * @var RESTRequest
     */
    public $request;

    /**
     * Application constructor.
     * @param string $apiKey
     * @param string $secretKey
     * @param string $msgNo
     * @param int $timestamp
     */
    public function __construct(string $apiKey = '', string $secretKey = '', int $timestamp = 0, string $msgNo = '')
    {
        parent::__construct($apiKey,$secretKey,$timestamp);
        $this->msgNo = $msgNo;

    }

    /**
     * Execute request to BithumbGlobal
     * @param RESTRequestInterface $request
     * @return string
     */
    public function execute(RESTRequestInterface $request)
    {
        $this->request = $request;
        $params = $request->getParams();
        if ($request->isNeedSign()) {
            $params["apiKey"] = $this->apiKey;
            $params["msgNo"] = $this->msgNo;
            $params["timestamp"] = $this->timestamp;
            $params['version']=$this->version;
            $params["signature"] = $this->genSignature($params);
        }
        if ($request->getMethod() == "POST") {
            $params = json_encode($params);
        } else {
            $params = http_build_query($params);
        }
        $url = $this->baseEndpoint . $request->getPath();
        $curl = new CurlUtil();
        return $curl->request($request->getMethod(), $url, $params);
    }

    /**
     * Generate signature string
     * @param  array    $params
     * @return string
     */
    private function genSignature(array $params)
    {
        ksort($params);
        $str = http_build_query($params);
        $sign = hash_hmac("sha256", $str, $this->secretKey, false);
        return $sign;
    }

    /**
     * Execute Request and get Response object
     * @param RESTRequestInterface $request
     * @return RESTResponse
     */
    public function getResponse(RESTRequestInterface $request):RESTResponse{
        $rawResponse = $this->execute($request);
        $this->response = new RESTResponse($rawResponse);
        return $this->response;
    }/**
     * Get Request object
     * @return RESTRequestInterface
     */
    public function getRequest():RESTRequestInterface{
        return $this->request;
    }
}