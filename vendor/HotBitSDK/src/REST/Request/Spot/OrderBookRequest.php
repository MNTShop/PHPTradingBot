<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class OrderBookRequest extends RESTRequest
{

    protected $path = "/spot/orderBook";

    protected $method = "GET";

    protected $isNeedSign = false;

    public $symbol;

    public function __construct(string $symbol='ALL')
    {
        $this->symbol=$symbol;
    }

    public function getParams():array
    {
        return [
            "symbol" => $this->symbol
        ];
    }

}