<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class MyTradesRequest extends RESTRequest
{

    protected $path = "/spot/ticker";

    protected $method = "post";

    protected $isNeedSign = true;

    public $symbol; //Y		String
    public $startTime;//trades start time	N		Long
    public $limit;//N		Integer

    public function __construct(string $symbol,int $startTime, int $limit )
    {
        $this->symbol=$symbol;
        $this->startTime=$startTime;
        $this->limit=$limit;
    }

    public function getParams():array
    {
        return [
            "symbol" => $this->symbol,
            "startTime" => $this->startTime,
            "limit" => $this->limit
        ];
    }

}