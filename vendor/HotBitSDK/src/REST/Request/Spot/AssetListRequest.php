<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:03 AM
 */

namespace HotBitSDK\REST\Request\Spot;
use HotBitSDK\REST\RESTRequest;

class AssetListRequest extends RESTRequest
{

    protected $path = "/spot/assetList";

    protected $method = "POST";

    protected $isNeedSign = true;

    public $coinType;

    public $assetType;

    public function __construct(string $assetType='ALL',string $coinType='null')
    {
        $this->coinType = $coinType;
        $this->assetType= $assetType;
    }

    public function getParams():array
    {
        return [
            "coinType" => $this->coinType,
            "assetType" => $this->assetType
        ];
    }

}