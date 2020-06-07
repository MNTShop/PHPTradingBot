<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;


class SingleOrderRequest extends RESTRequest
{

    protected $path = "/spot/singleOrder";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $orderId;
    public $symbol;

    public function __construct(string $orderId,string $symbol)
    {
        $this->symbol = $symbol;
        $this->orderId = $orderId;
    }

    public function getParams():array
    {
        return [
            "orderId" => $this->orderId,
            "symbol" => $this->symbol
        ];
    }

}