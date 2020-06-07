<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request;
use HotBitSDK\REST\RESTRequest;

class WithdrawRequest extends RESTRequest
{

    protected $path = "/withdraw";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $coinType;//		Y	e.g BTC,USDT(USDT-ERC20,USDT-OMNI)	String
    public $address;//	target wallet address	Y		String
    public $extendParam; //	memo or tag	N		String
    public $quantity;//		Y		String
    public $mark	;//	Y	max support for 250 char	String

    public function __construct(string $coinType, string $address, string $mark, string $quantity, string $extendParam ='')
    {
        $this->coinType=$coinType;//		Y	e.g BTC,USDT(USDT-ERC20,USDT-OMNI)	String
        $this->address=$address;//	target wallet address	Y		String
        $this->extendParam=$extendParam;//	memo or tag	N		String
        $this->quantity=$quantity;//		Y		String
        $this->mark	=$mark;
    }

    public function getParams():array
    {
        return [
            "coinType" => $this->coinType,
            "address" => $this->address,
            "extendParam" => $this->extendParam,
            "quantity" => $this->quantity,
            "mark" => $this->mark
        ];
    }

}