<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;


class KlineRequest extends RESTRequest
{

    protected $path = "/spot/kline";

    protected $method = "GET";

    protected $isNeedSign = false;

    public $symbol;
    public $type;       //order type		limit(limit price) or market(market price)	String
    public $start;
    public $end;

    public function __construct(string $symbol, string $type, int $startTimestamp,int $endTimestamp)
    {
        $this->symbol=$symbol;
        $this->type=$type;
        $this->start=$startTimestamp;
        $this->end=$endTimestamp;
    }

    public function getParams():array
    {
        return [
            "symbol" => $this->symbol,
            "type" => $this->type,
            "start" => $this->start,
            "end" => $this->end,
        ];
    }

}