<?php

namespace App\Console\Commands;

use App\BithumbTradeHelper;
use App\Brick;
use App\Modules;
use App\Order;
use App\Price;
use App\Setting;
use App\Ticker as TickerModel;
use App\Wall;
use Bg\Sdk\Examples\REST\ServerTimeExample;
use Bg\Sdk\REST\Request\Spot\CancelOrdersRequest;
use Bg\Sdk\REST\Request\Spot\OrderDetailRequest;
use Bg\Sdk\REST\Request\Spot\PlaceOrderRequest;
use Bg\Sdk\REST\Request\Spot\SingleOrderRequest;
use Bg\Sdk\WS\Streams\OrderStream;
use Bg\Sdk\WS\WSResponse;
use Bg\Sdk\WS\Streams\TickerStream;
use Bg\Sdk\BithumbGlobalClient;
use Bg\Sdk\WS\Interfaces\WSClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use \Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;

class Waller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daemon:waller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listens to ordersChange web socket';


    /**
     * Exchange client.
     *
     * @var BithumbGlobalClient
     */
    protected $exchangeClient ;
    /**
     * Waller Module instance.
     *
     * @var Modules
     */
    protected $waller ;
    /**
     * Amount to buy for order
     */
    protected $buyOrderAmount ;
    /**
     * spred or distance among orders
     */
    protected $spread ;

    /**
     * green wall covering in %
     */
    protected $buyCovering ;
    /**
     * red wall covering in %
     */
    protected $sellCovering ;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
                    $this->info('Daemon start');
        while(true) {
            try{
                $enabledModules = Modules::getActiveModules();
            $eligibleModules = [];
            if ($enabledModules) {
                foreach ($enabledModules as $module) {
                    $_module = $module->getFactory();
                    if (method_exists($_module, 'signalLoop')) {
                        $eligibleModules[] = $_module;
                    }
                }
            }
            //init waller and new settings
                $this->exchangeClient = BithumbTradeHelper::getBithumb();

                $this->waller = Modules::init('Waller');
//            $pairs = [$this->waller->getConfig('pair')];
            $pairs = ['BIP-USDT','BIP-BTC'];
                foreach ($pairs as $pair ){
                    // set pair settings
                    if($pair == 'BIP-BTC'){
                        $this->buyOrderAmount =  0.000421600;
                        $this->spread = 0.5;
                        $this->buyCovering = 2;
                        $this->sellCovering = 2;
                        $this->spread = $this->spread/100;
                    }else{
                        $this->buyCovering = $this->waller->getConfig('buyCovering');
                        $this->sellCovering = $this->waller->getConfig('sellCovering');
                        $this->spread = (float)$this->waller->getConfig('spread');
                        $this->spread = $this->spread/100;
                        $this->buyOrderAmount = $this->waller->getConfig('buyOrderAmount');
                    }

                    // from bithumb
                    $symbolConfig = BithumbTradeHelper::getNotions($pair);
                    $openBithumbOrdersArray = BithumbTradeHelper::getOpenOrdersId($this->exchangeClient,$pair);
                    $openWallerOrdersArray = Brick::getAllBricksOrderId($pair);
                    //create wall if not exist in waller memmory
                    if (empty($openWallerOrdersArray)) {
                        $this->createWall($pair,'sell');
                        $this->createWall($pair,'buy');
                        // go to next pair
                        continue;
                    }


//            $this->info('openWallerOrders' . print_r($openWallerOrdersArray, 1));
                    //close orders
                    $closedOrders = array_diff($openWallerOrdersArray, $openBithumbOrdersArray);
//                    sort($closedOrders,SORT_NUMERIC);
//            $this->info('$closedOrders  ' . print_r($closedOrders, 1));



                    $currentPrice = BithumbTradeHelper::getPrice($pair);

                    if (count($closedOrders) > 0) {

                        // recreate walls
                        foreach ($closedOrders as $orderId) {
                            //if closed by user just destroy
                            if(!$this->exchangeClient->getResponse(new SingleOrderRequest($orderId,$pair))->isError()){
                                if($this->exchangeClient->response->getData()->status == 'cancel'){
                                    //just remove brick
                                    Brick::destroyBrickByOrderId($orderId);
                                    continue;
                                }else{
                                    $this->info( print_r($this->exchangeClient->response->getData(),1));
                                    $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
                                    continue;

                                }
                            }

                            $otherWallBrick = new Brick();
                            $oldBrick = Brick::where('orderId', $orderId)->get()[0];

                            $otherWallBrick->symbol = $pair;
                            $otherWallBrick->type = 'limit';

                            if ($oldBrick->side == 'sell') {

                                $otherWallBrick->side = 'buy';
                                $percent = (float)$oldBrick->price * $this->spread;
                                $otherWallBrick->price = number_format((float)$oldBrick->price - $percent, $symbolConfig->accuracy[0], '.', '');
                                $otherWallBrick->quantity = number_format($this->buyOrderAmount/$otherWallBrick->price, $symbolConfig->accuracy[1], '.', '');

                            } else {
                                //                    $brick->quantity = number_format($_quantity*$this->spread + $_quantity , $symbolConfig->accuracy[1],'.','');
                                $otherWallBrick->side = 'sell';
                                $otherWallBrick->quantity = $oldBrick->quantity;
                                $otherWallBrick->price = number_format(($oldBrick->price * $this->spread) + $oldBrick->price, $symbolConfig->accuracy[0], '.', '');

                            }
                            if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                                $otherWallBrick->symbol, $otherWallBrick->type, $otherWallBrick->side, $otherWallBrick->price, $otherWallBrick->quantity, (string)ServerTimeExample::getTimestamp()
                            ))->isError()) {
                                $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
                                $this->info($otherWallBrick->quantity . 'Params : ' . print_r($otherWallBrick->price, 1).$otherWallBrick->side);
//                        $this->info('Daemon Waller error ' . print_r($otherWallBrick ,1));


                            } else {
                                $otherWallBrick->orderId = $this->exchangeClient->response->getData()->orderId;
                                $otherWallBrick->save();
                                Brick::destroyBrickByOrderId($orderId);

                                // if we sell 1 order try to restore wall
                                if(Brick::getRedBricks($pair)->isEmpty()){
                                    $this->info('Daemon Waller HERE red ' . print_r($otherWallBrick->price,1));
                                    $this->createWall($pair,'sell',$otherWallBrick->price );
                                }
                                if(Brick::getGreenBricks($pair)->isEmpty()){
                                    $this->info('Daemon Waller HERE green' . print_r($otherWallBrick->price,1) );
                                    $this->createWall($pair,'buy',$otherWallBrick->price );
                                }

                            }

                        }

                    }
//price trailing
                    $greenBricks = Brick::getGreenBricks($pair);
                    $redBricks = Brick::getRedBricks($pair);
//

                    if($greenBricks->isEmpty() && !$redBricks->isEmpty()){
                        //recreate walls if current price goes much down
                        $priceMin = (float)$redBricks[0]->price;
                        foreach ($redBricks as $redBrick){
                            if((float)$redBrick->price < $priceMin  ){
                                $priceMin = (float)$redBrick->price;
                            }
                        }
                        $price_new = $priceMin*$this->spread;
                        $floatPrice = $priceMin-$price_new;
                        $this->info('Daemon$redBricks $floatPrice ' . $floatPrice);
                        $this->info('Daemon Waller  current pair price ' . $currentPrice);

                        if($floatPrice > (float)$currentPrice) {
                            //create wall from current price
                            if (!$this->exchangeClient->getResponse(new CancelOrdersRequest($openWallerOrdersArray, $pair))->isError()) {
                                foreach ($openWallerOrdersArray as $orderId) {
                                    Brick::destroyBrickByOrderId($orderId);
                                }
                                //add 1 brick to collection
                                $otherWallBrick = new Brick();
                                $otherWallBrick->symbol = $pair;
                                $otherWallBrick->type = 'limit';
                                $otherWallBrick->side = 'sell';
                                $otherWallBrick->quantity = $redBricks[0]->quantity;
                                $otherWallBrick->price = $currentPrice;

                                if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                                    $pair, $otherWallBrick->type, $otherWallBrick->side, $otherWallBrick->price, $otherWallBrick->quantity, (string)ServerTimeExample::getTimestamp()
                                ))->isError()) {
                                    $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
                                    $this->info($otherWallBrick->quantity . 'Params : ' . print_r($otherWallBrick->price, 1) . $otherWallBrick->side);
                                } else {
                                    $otherWallBrick->orderId = $this->exchangeClient->response->getData()->orderId;
//                                    $otherWallBrick->createTime = $this->exchangeClient->response->getTimestamp();

                                    $otherWallBrick->save();
                                }
                            }
                        }
                    }elseif($redBricks->isEmpty() && !$greenBricks->isEmpty() ){
                        //get current price
                        $priceMax = (float)$greenBricks[0]->price;

                        foreach ($greenBricks as $greenBrick){
                            if((float)$greenBrick->price > $priceMax ){
                                $priceMax = (float)$greenBrick->price;
                            }
                        }
                        $price_new = $priceMax*$this->spread;
                        $floatPrice = $priceMax + $price_new ;
                        $this->info('$greenBricks $floatPrice' . $floatPrice);
                        $this->info('$greenBricks $price_new' . $price_new);
                        $this->info('current price  ' . $currentPrice);
                        $this->info('$priceMax ' . $currentPrice);
                        $this->info('$priceMax' . $priceMax);
                        if($floatPrice < (float)$currentPrice){
                            //create wall from current price
                            if(!$this->exchangeClient->getResponse(new CancelOrdersRequest($openWallerOrdersArray,$pair))->isError()){
                                foreach ($openWallerOrdersArray as $orderId){
                                    Brick::destroyBrickByOrderId($orderId);
                                }
                                //add 1 brick to collection
                                $otherWallBrick = new Brick();
                                $otherWallBrick->symbol = $pair;
                                $otherWallBrick->type = 'limit';
                                $otherWallBrick->side = 'buy';
                                $otherWallBrick->quantity = $greenBricks[0]->quantity;
                                $otherWallBrick->price = $currentPrice;

                                if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                                    $pair, $otherWallBrick->type, $otherWallBrick->side, $otherWallBrick->price, $otherWallBrick->quantity, (string)ServerTimeExample::getTimestamp()
                                ))->isError()) {
                                    $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
                                    $this->info($otherWallBrick->quantity . 'Params : ' . print_r($otherWallBrick->price, 1).$otherWallBrick->side);
                                } else {
                                    $otherWallBrick->orderId = $this->exchangeClient->response->getData()->orderId;
//                                    $otherWallBrick->createTime = $this->exchangeClient->response->getTimestamp();

                                    $otherWallBrick->save();
                                }
                            }
                        }
                    }
                }
                Cache::set('lastWallUpdate', time());
                sleep(1);
                    } catch (\Exception $exception) {
                        $this->alert($exception->getMessage());
                        continue;
//                        return Artisan::call("daemon:waller", []);
            }
//

        }
    }


    /**
     * Create wall on pair with defined side from current price if $pricebrick=0 or from  defined price
     * @param string $pair
     * @param string $side
     * @param float $pricebrick
     */
    public function createWall($pair, $side, $pricebrick=0){

        $symbolConfig = BithumbTradeHelper::getNotions($pair);
        $currentPrice = (float)BithumbTradeHelper::getPrice($pair);
//create wall from scratch
        $countSellWall = $this->sellCovering /($this->spread*100);
        $countBuyWall = $this->buyCovering / ($this->spread*100);
//        $this->spread = $this->spread / 100;
        $timestamp = (string)ServerTimeExample::getTimestamp();

        if($side == 'sell'){
            for ($i = 1; $i <= $countSellWall; $i++) {
                $brick = new Brick();
                $brick->side = 'sell';
                $brick->type = 'limit';
                $brick->symbol = $pair;
                //get price first brick if price empty
                if ($pricebrick == 0) {
                    $pricebrick = $currentPrice+($currentPrice * $this->spread);
                } else {
                    $pricebrick = ($pricebrick * $this->spread) + $pricebrick;
                }
                $brick->price = number_format($pricebrick, $symbolConfig->accuracy[0], '.', '');
                $_quantity = $this->buyOrderAmount / $pricebrick;
                $brick->quantity = number_format($_quantity * $this->spread + $_quantity, $symbolConfig->accuracy[1], '.', '');
                // new order and todo subscribe to

                if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                    $brick->symbol, $brick->type, $brick->side, $brick->price, $brick->quantity, $timestamp
                ))->isError()) {

//                    $this->info('Daemon Waller error ' . print_r($this->exchangeClient->request,1));
                    $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
//                        $this->info('Daemon Waller error ' . print_r($this->exchangeClient-> ,1));
//                        $this->info('Daemon Waller error ' . print_r($brick ,1));

                    $this->info(number_format($brick->quantity, $symbolConfig->accuracy[1], '.', '') . 'Params : ' . print_r(number_format($brick->price, $symbolConfig->accuracy[0], '.', ''), 1).$brick->side);

                } else {
                    $brick->orderId = $this->exchangeClient->response->getData()->orderId;
//                    $brick->createTime = $this->exchangeClient->response->getTimestamp();

                    $brick->save();
                }
            }
        }
        else{
            for ($i = 1; $i <= $countBuyWall; $i++) {
                $brick = new Brick();
                //    protected $fillable = ['side', 'symbol', 'price', 'quantity', 'orderId', 'createTime','tradedNum'];
                $brick->side = 'buy';
                $brick->symbol = $pair;
                $brick->type = 'limit';
                //get price first brick if price empty
                if ($pricebrick == 0) {
                    $pricebrick = $currentPrice - ($currentPrice * $this->spread);
                } else {
                    $pricebrick = $pricebrick - ($pricebrick * $this->spread);
                }
                $brick->price = number_format($pricebrick, $symbolConfig->accuracy[0], '.', '');
                $_quantity = $this->buyOrderAmount/$brick->price;
                $brick->quantity = number_format($_quantity, $symbolConfig->accuracy[1], '.', '');
                // new order and subscribe to
                if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                    $brick->symbol, $brick->type, $brick->side, $brick->price, $brick->quantity, $timestamp
                ))->isError()) {
                    if($this->exchangeClient->response->getCode()==20003){
//                            $brick->save();
                    }
                    $this->info('Daemon Waller error ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
//                        $this->info('Daemon Waller error ' . print_r($brick ,1));

//                    $this->info('Daemon $brick->price ' . $brick->price . ' $brick->quantity' . $brick->quantity .$brick->side);
//                        $this->info('Daemon $symbolConfig->accuracy ' . print_r($symbolConfig->accuracy, 1) . ' $brick->quantity' . $brick->quantity);

                } else {
                    $brick->orderId = $this->exchangeClient->response->getData()->orderId;
//                    $brick->createTime = $this->exchangeClient->response->getTimestamp();

                    $brick->save();
                }
            }
        }


    }

}
