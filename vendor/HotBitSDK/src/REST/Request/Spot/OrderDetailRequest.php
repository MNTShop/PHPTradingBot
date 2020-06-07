<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class OrderDetailRequest extends RESTRequest
{

    protected $path = "/spot/orderDetail";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $orderId	;//	Y		String
    public $symbol	;//	Y		String
    public $page	;//current page	N	default = 1	String
    public $count	;//current page count	N	default = 10	String

    public function __construct(string $orderId,string $symbol,string $page='1',string $count='10')
    {
        $this->orderId = $orderId;
        $this->symbol= $symbol;
        $this->page= $page;
        $this->count= $count;
    }

    public function getParams():array
    {
        return [
            "orderId" => $this->orderId,
            "symbol" => $this->symbol,
            "page" => $this->page,
            "count" => $this->count,
        ];
    }

}