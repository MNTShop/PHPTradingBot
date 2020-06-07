<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class PlaceOrderRequest extends RESTRequest
{

    protected $path = "/spot/placeOrder";

    protected $method = "POST";

    protected $isNeedSign = true;


    public $symbol;
    public $type;       //order type	Y	limit(limit price) or market(market price)	String
    public $side;       //  	order side	Y	buy or sell	String
    public $price;      //		Y	when type is market, the value = -1	String
    public $quantity;   //		Y	eg.BTC-USDT,normally point at the quantity of BTC,when type=market,side=buy,point at the quantity of USDT, and the quantity should greater than or equal to the minimum trading volume of BTC	String
    public $timestamp;  //		Y		String

    public function __construct(string $symbol, string $type, string $side, string $price, string $quantity, string $timestamp)
    {

        $this->symbol=$symbol;
        $this->type=$type;
        $this->side=$side;
        $this->price=$price;
        $this->quantity=$quantity;
        $this->timestamp=$timestamp;
    }

    public function getParams():array
    {
        return [
            "symbol" => $this->symbol,
            "type" => $this->type,
            "side" => $this->side,
            "price" => $this->price,
            "quantity" => $this->quantity,
            "timestamp" => $this->timestamp
        ];
    }

}