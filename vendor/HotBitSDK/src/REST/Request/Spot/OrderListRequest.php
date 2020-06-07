<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class OrderListRequest extends RESTRequest
{

    protected $path = "/spot/orderList";

    protected $method = "POST";

    protected $isNeedSign = true;
    public $side;//	order side（buy，sell）	Y		String
    public $symbol	;//	Y		String
    public $status;//	order status（traded (history order)）	Y		String
    public $queryRange;//	the range of order（thisweek(in 7 day)，thisweekago(before 7 ago)）	Y		String
    public $page	;//current page	N		String
    public $count	;//current page count	N		String

    public function __construct(string $side,string $symbol,string $status,string $queryRange,string $page='',string $count='')
    {
        $this->side = $side;
        $this->symbol= $symbol;
        $this->status= $status;
        $this->queryRange= $queryRange;
        $this->page= $page;
        $this->count= $count;
    }

    public function getParams():array
    {
        return [
            "side" => $this->side,
            "symbol" => $this->symbol,
            "status" => $this->status,
            "queryRange" => $this->queryRange,
            "page" => $this->page,
            "count" => $this->count,
        ];
    }

}