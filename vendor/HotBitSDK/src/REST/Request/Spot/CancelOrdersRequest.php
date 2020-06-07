<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;


class CancelOrdersRequest extends RESTRequest
{

    protected $path = "/spot/cancelOrder";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $orderIds;
    public $symbol;

    public function __construct(array $orderIds,string $symbol)
    {
        $this->symbol = $symbol;
        $this->orderIds = $orderIds;
    }

    public function getParams():array
    {
        return [
            "ids" => implode(',',$this->orderIds),
            "symbol" => $this->symbol
        ];
    }

}