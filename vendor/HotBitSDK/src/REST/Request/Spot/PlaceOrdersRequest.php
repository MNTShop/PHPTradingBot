<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;


class PlaceOrdersRequest extends RESTRequest
{

    protected $path = "/spot/placeOrders";

    protected $method = "POST";

    protected $isNeedSign = true;
    public $multiParams=[];		//Y	Array of single place's order parameters，0 < order's list of size <= 10	String


    public function __construct(array $OrdersObjectsArr = [])
    {
        $this->multiParams=$OrdersObjectsArr;
    }

    public function getParams():array
    {
        return [
            "multiParams" => serialize($this->multiParams)
        ];
    }

}