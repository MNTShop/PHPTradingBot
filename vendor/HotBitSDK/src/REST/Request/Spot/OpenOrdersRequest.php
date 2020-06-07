<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class OpenOrdersRequest extends RESTRequest
{

    protected $path = "/spot/openOrders";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $symbol	;//	Y		String
    public $page	;//current page	N		String
    public $count	;//current page count	N		String

    public function __construct(string $symbol,string $page='',string $count='')
    {
        $this->symbol= $symbol;
        $this->page= $page;
        $this->count= $count;
    }

    public function getParams():array
    {
        return [
            "symbol" => $this->symbol,
            "page" => $this->page,
            "count" => $this->count,
        ];
    }


}