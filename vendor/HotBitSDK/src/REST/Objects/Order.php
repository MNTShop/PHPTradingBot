<?php
/**
 * Created by PhpStorm.
 * User: devacc
 * Date: 25.05.2020
 * Time: 01:00
 */

namespace HotBitSDK\Objects;


class Order
{

    public $orderId;    //			String
 //place Order
    /** String
     * @var string
     */
    public $symbol;//		Y		String like fsymbol
    public $type;//	order type	Y	limit(limit price) or market(market price)	String
    public $side;	//order side	Y	buy or sell	String
    public $price;		//Y	when type is market, the value = -1	String
    public $quantity;		//Y	eg.BTC-USDT,normally point at the quantity of BTC,when type=market,side=buy,point at the quantity of USDT, and the quantity should greater than or equal to the minimum trading volume of BTC	String

    public $timestamp;      //	order create time		Date == $createTime

    //orderList
    public $tradedNum;	//completed quantity		Decimal
    public $avgPrice;//	average price		Decimal
    public $status;//	order status	send，pending，success，cancel	String
    public $createTime;//	order create time		Date == $timestamp
    public $tradeTotal;//			Decimal

    //Order Detail
    public $orderSign;  //	order status	deal by taker or maker?	String
    public $getCount;       //	get		String
    public $getCountUnit; //	coin type		String
    public $loseCount;  //	lose		String
    public $loseCountUnit;  //	coin type		String
    public $priceUnit;  //	coin type		String
    public $fee;    //	deal fee	fee	String
    public $feeUnit;    //	coin type		String
    public $time;   //	time		Long
    public $fsymbol;    //	symbol	BTC-USDT	String

//string $symbol,string $type,string $side,string $price,string $quantity
    public function __construct(array $orderArr =[])
    {
        $this->orderId =(!empty($orderArr['orderId']))?(string)$orderArr['orderId']:'';
        $this->type =(!empty($orderArr['type']))?(string)$orderArr['type']:'';
        $this->symbol =(!empty($orderArr['symbol']))?(string)$orderArr['symbol']:(!empty($orderArr['fsymbol']))?$orderArr['fsymbol']:'';
        $this->side =(!empty($orderArr['side']))?(string)$orderArr['side']:'';
        $this->price =(!empty($orderArr['price']))?(string)$orderArr['price']:'';
        $this->quantity =(!empty($orderArr['quantity']))?(string)$orderArr['quantity']:'';
        $this->timestamp =(!empty($orderArr['timestamp']))?(int)$orderArr['timestamp']:(!empty($orderArr['createTime']))?$orderArr['createTime']:'';

        $this->tradedNum =(!empty($orderArr['tradedNum']))?(float)$orderArr['tradedNum']:'';
        $this->avgPrice =(!empty($orderArr['avgPrice']))?(float)$orderArr['avgPrice']:'';
        $this->status =(!empty($orderArr['status']))?(string)$orderArr['status']:'';
        $this->createTime =(!empty($orderArr['createTime']))?(int)$orderArr['createTime']:(!empty($orderArr['timestamp']))?$orderArr['timestamp']:'';
        $this->tradeTotal =(!empty($orderArr['tradeTotal']))?(float)$orderArr['tradeTotal']:'';

        $this->orderSign =(!empty($orderArr['orderSign']))?(string)$orderArr['orderSign']:'';
        $this->getCount =(!empty($orderArr['getCount']))?(string)$orderArr['getCount']:'';
        $this->getCountUnit =(!empty($orderArr['getCountUnit']))?(string)$orderArr['getCountUnit']:'';
        $this->loseCount =(!empty($orderArr['loseCount']))?(string)$orderArr['loseCount']:'';
        $this->loseCountUnit =(!empty($orderArr['loseCountUnit']))?(string)$orderArr['loseCountUnit']:'';
        $this->priceUnit =(!empty($orderArr['priceUnit']))?(string)$orderArr['priceUnit']:'';
        $this->fee =(!empty($orderArr['fee']))?(string)$orderArr['fee']:'';
        $this->feeUnit =(!empty($orderArr['feeUnit']))?(string)$orderArr['feeUnit']:'';
        $this->time =(!empty($orderArr['time']))?(string)$orderArr['time']:'';
        $this->fsymbol = (!empty($orderArr['fsymbol']))?(string)$orderArr['fsymbol']:(!empty($orderArr['symbol']))?(string)$orderArr['symbol']:'';    //	symbol	BTC-USDT	String

    }

}