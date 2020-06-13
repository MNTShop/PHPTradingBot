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
                $pairs = [$this->waller->getConfig('pair')];
//            $pairs = ['BIP-USDT','BIP-BTC']; // uncomment to trade on desire pairs
                foreach ($pairs as $pair ){
                    // set pair settings
                    if($pair == 'BIP-BTC'){
                        $this->buyOrderAmount =  0.000421600;
                        $this->spread = 2;
                        $this->buyCovering = 20;
                        $this->sellCovering = 20;
                        $this->spread = $this->spread/100;
                    }else{
                        $this->buyCovering = $this->waller->getConfig('buyCovering');
                        $this->sellCovering = $this->waller->getConfig('sellCovering');
                        $this->spread = (float)$this->waller->getConfig('spread');
                        $this->spread = $this->spread/100;
                        $this->buyOrderAmount = $this->waller->getConfig('buyOrderAmount');
                    }

                    // from bithumb
                    $openBithumbOrdersArray = BithumbTradeHelper::getOpenOrdersId($this->exchangeClient,$pair);
                    if(empty($openBithumbOrdersArray)){
                        //destroy all bricks in waller
                        Brick::destroyWall($pair);
                    }
                    $openWallerOrdersArray = Brick::getAllBricksOrderId($pair);
                    //create wall if not exist in waller memmory
                    $currentPrice = BithumbTradeHelper::getPrice($pair);
                    if (empty($openWallerOrdersArray)) {
                        $this->createWall($pair,'sell',$currentPrice);
                        $this->createWall($pair,'buy',$currentPrice);
                        // go to next pair
                        continue;
                    }


                    //get first greenBRick Price
                    $GreenBricks = Brick::getGreenBricks($pair);
                    $RedBricks = Brick::getRedBricks($pair);

                    if(!$GreenBricks->isEmpty()){
                        $firstGreenBrickPrice = $GreenBricks[0]->price;
                        if($currentPrice<$firstGreenBrickPrice){
                            //price goes down
                            foreach ($GreenBricks as $green_brick){
                                if($this->checkBrick($green_brick)){
                                    $oppositeBrick = $this->placeOppositeBrick($green_brick);
                                    if($oppositeBrick!==0){
                                        $oppositeBrick->save();
                                    }
                                    if($green_brick->type == 'trailing'){
                                        //try to reconstruct green wall
                                        $this->createWall($pair,'buy',$currentPrice);
                                    }
                                }
                            }
                        }
                        $GreenBricks = Brick::getGreenBricks($pair);
                        $RedBricks = Brick::getRedBricks($pair);
                        //trailing price if red wall not exist
                        if($RedBricks->isEmpty()){
                            $this->priceTrailingBrick($GreenBricks[0],$currentPrice);

                        }
                    }
                    if(!$RedBricks->isEmpty()){
                        $firstRedBrickPrice = $RedBricks[0]->price;
                        if($currentPrice>$firstRedBrickPrice){
                            //price goes up check firstly redwall
                            foreach ($RedBricks as $red_brick){
                                if($this->checkBrick($red_brick)){
                                    $oppositeBrick = $this->placeOppositeBrick($red_brick);
                                    if($oppositeBrick!==0){
                                        $oppositeBrick->save();
                                    }
                                    if($green_brick->type == 'trailing'){
                                        //try to reconstruct red wall
                                        $this->createWall($pair,'sell',$currentPrice);
                                    }
                                }
                            }
                        }
                        //trailing price
                        $GreenBricks = Brick::getGreenBricks($pair);
                        $RedBricks = Brick::getRedBricks($pair);
                        //trailing price if red wall not exist
                        if($GreenBricks->isEmpty()){
                            //get current price
                            $this->priceTrailingBrick($RedBricks[0],$currentPrice);
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

    public function checkBrick($brick){
        if(!$this->exchangeClient->getResponse(new SingleOrderRequest($brick->orderId,$brick->symbol))->isError()){
            if($this->exchangeClient->response->getData()->status == 'success'){
                //need to place new brick
                Brick::destroyBrickByOrderId($brick->orderId);
                return 1;

            }
            elseif($this->exchangeClient->response->getData()->status == 'cancel'){
                //just remove brick
                Brick::destroyBrickByOrderId($brick->orderId);
                return 0;
            }
        }else{
            $this->info('Daemon Waller error checkBrick request ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
        }
        return 0;
    }
    public function placeOppositeBrick($destroyedBrick){
        //need place new opposite brick
        $symbolConfig = BithumbTradeHelper::getNotions($destroyedBrick->symbol);
        $opositeBrick = new Brick();
        $opositeBrick->symbol = $destroyedBrick->symbol;
        $opositeBrick->type = 'limit';
        $opositeBrick->side = ($destroyedBrick->side == 'sell')?'buy':'sell';

        $percent = ($opositeBrick->side == 'sell')?(float)$destroyedBrick->price * $this->spread:(float)$destroyedBrick->price * $this->spread * -1;
        $opositeBrick->price = number_format((float)$destroyedBrick->price + $percent, $symbolConfig->accuracy[0], '.', '');
        $opositeBrick->quantity = ($opositeBrick->side == 'sell') ? $destroyedBrick->quantity : number_format($this->buyOrderAmount/$opositeBrick->price, $symbolConfig->accuracy[1], '.', '');

        if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
            $opositeBrick->symbol, $opositeBrick->type, $opositeBrick->side, $opositeBrick->price, $opositeBrick->quantity, (string)ServerTimeExample::getTimestamp()
        ))->isError()) {
            $this->info('Daemon Waller error placeOppositeBrick' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
            $this->info($opositeBrick->quantity . 'Params : ' . print_r($opositeBrick->price, 1).$opositeBrick->side);
//                        $this->info('Daemon Waller error ' . print_r($otherWallBrick ,1));
            return 0;
        } else {
            return $opositeBrick;
        }
    }
    public function priceTrailingBrick($firstBrick,$currentPrice){
        //need place new opposite brick
        $openWallerOrdersArray = Brick::getAllBricksOrderId($firstBrick->symbol);

        //get current price
        $price = (float)$firstBrick->price;
        $add_interest =($firstBrick->side == 'sell')? $price*$this->spread*-1 : $price*$this->spread;
        $floatPrice = $price + $add_interest ;

        $this->info('$greenBricks $floatPrice' . $floatPrice);
        $this->info('$greenBricks $add_interest' . $add_interest);
        $this->info('current price  ' . $currentPrice);
        $this->info('$priceMax ' . $currentPrice);
        $this->info('$priceMax' . $price);

        if($floatPrice < (float)$currentPrice){
            //create wall from current price
            if(!$this->exchangeClient->getResponse(new CancelOrdersRequest($openWallerOrdersArray,$firstBrick->symbol))->isError()){
                foreach ($openWallerOrdersArray as $orderId){
                    Brick::destroyBrickByOrderId($orderId);
                }
                //add 1 brick to collection
                $trailingBrick = new Brick();
                $trailingBrick->symbol = $firstBrick->symbol;
                $trailingBrick->type = 'limit';
                $trailingBrick->side = $firstBrick->side;
                $trailingBrick->quantity = $firstBrick->quantity;
                $trailingBrick->price = $currentPrice;

                if ($this->exchangeClient->getResponse(new PlaceOrderRequest(
                    $firstBrick->symbol, $trailingBrick->type, $trailingBrick->side, $trailingBrick->price, $trailingBrick->quantity, (string)ServerTimeExample::getTimestamp()
                ))->isError()) {
                    $this->info('Daemon Waller error priceTrailingBrick ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
                    $this->info($trailingBrick->quantity . 'Params : ' . print_r($trailingBrick->price, 1).$trailingBrick->side);
                } else {
                    $trailingBrick->orderId = $this->exchangeClient->response->getData()->orderId;
//                                    $trailingBrick->createTime = $this->exchangeClient->response->getTimestamp();
                    $trailingBrick->type = 'trailing'; // mark as trailing

                    $trailingBrick->save();
                }
            }
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
        $val = $this->spread*100;
        $countSellWall = $this->sellCovering /$val;
        $countBuyWall = $this->buyCovering  /$val;
//        $this->spread = $this->spread / 100;
        $timestamp = (string)ServerTimeExample::getTimestamp();

        if($side == 'sell'){
            for ($i = 1; $i <= $countSellWall; $i++) {
                $brick = new Brick();
                $brick->side = 'sell';
                $brick->type = 'limit';
                $brick->symbol = $pair;
                //get price first brick if price empty
                $val = $currentPrice * $this->spread;
                if ($pricebrick == 0) {
                    $pricebrick = $currentPrice+$val;
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
                    $this->info('Daemon Waller error createWall ' . $this->exchangeClient->response->getCode() . $this->exchangeClient->response->getMessage());
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
