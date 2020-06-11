<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/12/18
 * Time: 01:18
 */

namespace App;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int id
 * @property string side
 * @property string timeInForce
 * @property string type
 * @property string status
 * @property int cummulativeQuoteQty
 * @property int executedQty
 * @property int origQty
 * @property float price
 * @property string symbol
 * @property int orderId
 * @property int clientOrderId
 * @property float|int transactTime
 * @property int buyId
 * @property mixed sellOrder
 * @property int takeProfit
 * @property int stopLoss
 * @property int trailingTakeProfit
 * @property float trailingStopLoss
 * @property mixed maxFloated
 * @property mixed minFloated
 * @property bool trailing
 * @property null comment
 * @property null signal_id
 * @property bool|float|int pl
 */
class Order extends Model
{
    protected $guarded = [];


    /**
     * @param $symbol
     * @param $quantity
     * @param null $comment
     * @param $options
     * @return bool
     * @throws \Exception
     */
    public static function buy($symbol, $quantity, $comment = null, $options = [],$fakeBuy = false)
    {
        $orderDefaults = Setting::getValue('orderDefaults');

        $side = 'BUY';
        $type = 'MARKET';
        $timeInForce = 'GTC';
        $timestamp = round(microtime(true) * 1000);
        /*
         * Module Hook
         */
        $activeModules = Modules::getActiveModules();
        $anyFails = false;
        if ($activeModules) {
            foreach ($activeModules as $module) {
                if ($module->getFactory()->beforeBuy() == false) {
                    $anyFails = true;
                }
            }
        }
        //From bithumb trader can BBuy?

        //
        $order = new Order();
        $order->symbol = $symbol;
        $order->side = $side;
        $order->origQty = $quantity;
        $order->type = $type;
        $order->timeInForce = $timeInForce;

        $order->takeProfit = isset($orderDefaults['tp']) ? $orderDefaults['tp'] : 2;
        $order->stopLoss = isset($orderDefaults['sl']) ? $orderDefaults['sl'] : 2;
        $order->trailingTakeProfit = isset($orderDefaults['ttp']) ? $orderDefaults['ttp'] : 1;
        $order->trailingStopLoss = isset($orderDefaults['tsl']) ? $orderDefaults['tsl'] : 0.5;

        if(!$fakeBuy && !Setting::getValue('trainingMode')) {
            // Bithumb trade helper make buy

            $response = BithumbTradeHelper::placeOrder(strtolower($type),$order->symbol,strtolower($order->side),'-1',$order->origQty);
//            error_log('Placed order: '.print_r($response,1));
            if($response == false){
                $anyFails =true;
            }else{
                $order->orderId = rand(1, 9999999);
                $order->clientOrderId = $response->orderId;


            }
            $response = BithumbTradeHelper::singleOrder($order->clientOrderId,$order->symbol);
            error_log(print_r($response,1));
            if($response){
                $order->transactTime = $response->createTime;
                $order->price = $response->avgPrice;
                $order->executedQty = $response->quantity;
                $order->cummulativeQuoteQty = $response->tradedNum;
                $order->status = $response->status;
            }else{
                $order->transactTime = $timestamp;
                $order->price = BithumbTradeHelper::getPrice($symbol);
                $order->executedQty = $quantity;
                $order->cummulativeQuoteQty = $quantity/$order->price;
                $order->status = 'FILLED';
            }

        }
        else{
            $order->orderId = rand(1, 9999999);
            $order->clientOrderId = rand(1, 999);
            $order->transactTime = $timestamp;
            $order->price = BithumbTradeHelper::getPrice($symbol);
            $order->executedQty = $quantity;
            $order->cummulativeQuoteQty = $quantity/$order->price;
            $order->status = 'FILLED';
        }


        if ($anyFails) {
            return false;
        }

        if (!empty($options)) {
            if (isset($options['tp']))
                $order->takeProfit = $options['tp'];
            if (isset($options['sl']))
                $order->stopLoss = $options['sl'];
            if (isset($options['ttp']))
                $order->trailingTakeProfit = $options['ttp'];
            if (isset($options['tsl']))
                $order->trailingStopLoss = $options['tsl'];
            if (isset($options['signal_id']))
                $order->signal_id = $options['signal_id'];
        }
        if ($comment) {
            $order->comment = $comment;
        }

        $order->save();

        /*
         * Modules after hook
         */
        if ($activeModules) {
            foreach ($activeModules as $module) {
                $module->getFactory()->afterBuy();
            }
        }

        return $order->id;
    }
    public static function addBrick(Brick $brick,$spread){
        /*
         * Module Hook
         */
        $activeModules = Modules::getActiveModules();
        $anyFails = false;
        if ($activeModules) {
            foreach ($activeModules as $module) {
                if ($module->getFactory()->beforeBuy() == false) {
                    $anyFails = true;
                }
            }
        }
        //From bithumb trader can BBuy?

        //
        $order = new Order();
        $order->comment = 'Waller';
        $order->symbol = $brick->symbol;
        $order->side =  $brick->side;
        $order->origQty = $brick->quantity;
        $order->type = $brick->type;
        $order->timeInForce = 'GTC';

        $order->takeProfit = $spread;

                $order->orderId = rand(1, 9999999);
                $order->clientOrderId = $brick->orderId;

                $order->transactTime = time();
                $order->price = $brick->price;
                $order->executedQty = $brick->quantity;
                $order->cummulativeQuoteQty = $brick->quantity;
                $order->status = 'FILLED';


        if ($anyFails) {
            return false;
        }

        if($brick->side == 'sell'){
            //found buy brick  last buy brick without
//            $order->buyId = $order->id;
//            self::
            $orders = Order::where('side', 'BUY')
                ->where('comment','=', 'Waller')
                ->where('symbol','=', $brick->symbol)
                ->whereDoesntHave('sellOrder')
                ->orderBy('created_at', 'desc')
                ->get();
            if(!$orders->isEmpty()){
                $order->buyId = $orders[0]->id;
//                $orders[0]->->buyId = $buyId;
            }
        }

        error_log(print_r($order,1));
        $order->save();
//        $order->save();


        /*
         * Modules after hook
         */
        if ($activeModules) {
            foreach ($activeModules as $module) {
                $module->getFactory()->afterBuy();
            }
        }

        return $order->id;
    }
    /**
     * @param $symbol
     * @param $quantity
     * @param null $comment
     * @param $options
     * @return bool
     * @throws \Exception
     */
    public static function buyLimit($symbol, $quantity, $comment = null, $options = [])
    {
        $orderDefaults = Setting::getValue('orderDefaults');

        $side = 'BUY';
        $type = 'LIMIT';
        $timeInForce = 'GTC';
        $timestamp = round(microtime(true) * 1000);
        /*
         * Module Hook
         */
        $activeModules = Modules::getActiveModules();
        $anyFails = false;
        if ($activeModules) {
            foreach ($activeModules as $module) {
                if ($module->getFactory()->beforeBuy() == false) {
                    $anyFails = true;
                }
            }
        }
        //From bithumb trader can BBuy?

        //
        $order = new Order();
        $order->symbol = $symbol;
        $order->side = $side;
        $order->origQty = $quantity;
        $order->type = $type;
        $order->timeInForce = $timeInForce;

        $order->takeProfit = isset($orderDefaults['tp']) ? $orderDefaults['tp'] : 2;
        $order->stopLoss = isset($orderDefaults['sl']) ? $orderDefaults['sl'] : 2;
        $order->trailingTakeProfit = isset($orderDefaults['ttp']) ? $orderDefaults['ttp'] : 1;
        $order->trailingStopLoss = isset($orderDefaults['tsl']) ? $orderDefaults['tsl'] : 0.5;
        $order->price = BithumbTradeHelper::getPrice($symbol);

        if(!Setting::getValue('trainingMode')) {
            // Bithumb trade helper make buy

            $response = BithumbTradeHelper::placeOrder(strtolower($type),$order->symbol,strtolower($order->side),$order->price,$order->origQty);
            error_log('Placed order: '.print_r($response,1));
            if($response == false){
                $anyFails =true;
            }else{
                $order->orderId = rand(1, 9999999);
                $order->clientOrderId = $response->orderId;


            }
            $response = BithumbTradeHelper::singleOrder($order->clientOrderId,$order->symbol);
            error_log(print_r($response,1));
            if($response){
                $order->transactTime = $response->createTime;
                $order->executedQty = $response->quantity;
                $order->cummulativeQuoteQty = $response->tradedNum;
                $order->status = $response->status;
            }else{
                $order->transactTime = $timestamp;
                $order->executedQty = $quantity;
                $order->cummulativeQuoteQty = $quantity/$order->price;
                $order->status = 'success';
            }

        }
        else{
            $order->orderId = rand(1, 9999999);
            $order->clientOrderId = rand(1, 999);
            $order->transactTime = $timestamp;
            $order->executedQty = $quantity;
            $order->cummulativeQuoteQty = $quantity/$order->price;
            $order->status = 'success';
        }


        if ($anyFails) {
            return false;
        }

        if (!empty($options)) {
            if (isset($options['tp']))
                $order->takeProfit = $options['tp'];
            if (isset($options['sl']))
                $order->stopLoss = $options['sl'];
            if (isset($options['ttp']))
                $order->trailingTakeProfit = $options['ttp'];
            if (isset($options['tsl']))
                $order->trailingStopLoss = $options['tsl'];
            if (isset($options['signal_id']))
                $order->signal_id = $options['signal_id'];
        }
        if ($comment) {
            $order->comment = $comment;
        }

        $order->save();

        /*
         * Modules after hook
         */
        if ($activeModules) {
            foreach ($activeModules as $module) {
                $module->getFactory()->afterBuy();
            }
        }

        return $order->id;
    }

    /**
     * @param $symbol
     * @param $quantity
     * @param $buyId
     * @param null $comment
     * @return bool
     * @throws \Exception
     */
    public static function sell($symbol, $quantity, $buyId, $comment = null)
    {
        $side = 'SELL';
        $type = 'MARKET';
        $timeInForce = 'GTC';
        $timestamp = round(microtime(true) * 1000);
        /*
         * Module before Hook
         */
        $activeModules = Modules::getActiveModules();
        $anyFails = false;
        if ($activeModules) {
            foreach ($activeModules as $module) {
                if ($module->getFactory()->beforeSell() == false) {
                    $anyFails = true;
                }
            }
        }
        $order = new Order();
        $order->symbol = $symbol;
        $order->side = $side;
        $order->origQty = $quantity;
        $order->type = $type;
        $order->timeInForce = $timeInForce;
        $order->buyId = $buyId;

        $buyOrder = self::find($buyId);
        $buyOrder->sell_date = now();

        if(!Setting::getValue('trainingMode')) {
            // Bithumb trade helper make buy
//            error_log('Placed $sellQuantity: '.$buyOrder->origQty.' '.$buyOrder->price);

//            $sellQuantity = $buyOrder->origQty/$buyOrder->price;
            $response = BithumbTradeHelper::placeOrder(strtolower($type),$order->symbol,strtolower($order->side),'-1',$buyOrder->cummulativeQuoteQty);
            error_log('Placed order: '.print_r($response,1));
            if(empty($response)){
                $anyFails = true;
            }else{
                $order->orderId = rand(1, 9999999);
                $order->clientOrderId = $response->orderId;


            }
            $response = BithumbTradeHelper::singleOrder($order->clientOrderId,$order->symbol);
            error_log(print_r($response,1));
            if($response){
                $order->transactTime = $response->createTime;
                $order->price = $response->avgPrice;
                $order->executedQty = $response->quantity;
                $order->cummulativeQuoteQty = $response->tradedNum;
                $order->status = $response->status;
            }else{
                $order->transactTime = $timestamp;
                $order->price = BithumbTradeHelper::getPrice($symbol);
                $order->executedQty = $quantity;
                $order->cummulativeQuoteQty = $quantity/$order->price;
                $order->status = 'success';
            }

        }
        else{
            $order->orderId = rand(1, 9999999);
            $order->clientOrderId = rand(1, 999);
            $order->transactTime = $timestamp;
            $order->price = BithumbTradeHelper::getPrice($symbol);
            $order->executedQty = $quantity;
            $order->cummulativeQuoteQty = $quantity/$order->price;
            $order->status = 'success';
        }

        if ($anyFails) {
            return false;
        }


        if ($comment) {
            $order->comment = $comment;
        }


        $order->save();
        $buyOrder->save();

        /*
         * Modules after hook
         */
        if ($activeModules) {
            foreach ($activeModules as $module) {
                $module->getFactory()->afterSell();
            }
        }
        return $order->id;
    }

    public static function getOpenPositions($noGroup = false)
    {
        $since = Carbon::now()->subDays(30);

        $orders = Order::where('created_at', '>', $since)
            ->where('side', 'BUY')
            ->where('comment','<>', 'Waller')
            ->whereDoesntHave('sellOrder')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($noGroup) {
            return $orders;
        }
        return $orders->groupBy('symbol');
    }

    public function sellOrder()
    {
        return $this->belongsTo(Order::class, 'id', 'buyId');
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class, 'signal_id', 'signalID');
    }

    public function isOpen()
    {
        if ($this->sellOrder == null && $this->side != 'SELL' && $this->type !='LIMIT') {
            return true;
        }
        return false;
    }

    public function getPL($history = false)
    {
        if (!$this->isOpen()) {
            if ($history) {
                $buyPrice = $this->price;
                $nowPrice = $this->sellOrder->price;
            } else {
                return false;
            }
        } else {
            $buyPrice = $this->price;
            $nowPrice = BithumbTradeHelper::getPrice($this->symbol);
        }


        return BithumbTradeHelper::getPercent($buyPrice, $nowPrice);
    }

    public function getCurrentPrice()
    {
        return BithumbTradeHelper::getPrice($this->symbol);
    }

    public function inProfit()
    {
        if ($this->getPL() > 0) {
            return true;
        }
        return false;
    }

    public static function getClosedPositions($noGroup = false)
    {
        $since = Carbon::now()->subDays(30);
        $orders = Order::where('created_at', '>', $since)
            ->where('side', 'BUY')
            ->whereHas('sellOrder')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($noGroup) {
            return $orders;
        }
        return $orders->groupBy('symbol');
    }

    public function getTimeFrame()
    {
        if (!$this->isOpen()) {
            $buyDate = Carbon::createFromTimestampMs($this->transactTime);
            $sellDate = Carbon::createFromTimestampMs($this->sellOrder->transactTime);
            return $buyDate->diffForHumans($sellDate, true);
        }
        return false;
    }

    public static function boot()
    {
        parent::boot();
        static::updating(function (Order $order) {
            $order->pl = $order->getPL(true);
        });
    }

    public function updateState()
    {
        if($this->type == 'LIMIT'){
            //send，pending，success，cancel
            //if trade in pending update totals
            if($this->status == 'pending'||$this->status == 'send'){
                //update status and order
                $response = BithumbTradeHelper::singleOrder($this->clientOrderId,$this->symbol);
                error_log(print_r($response,1));
                $this->transactTime = $response->createTime;
                $this->executedQty = $response->quantity;
                $this->cummulativeQuoteQty = $response->tradedNum;
                $this->status = $response->status;
            }if ($this->status != 'success'){
                return false;
            }
        }

        /*
         * updates the maxFloated
         */
        $pl = $this->getPL();
        if ($pl >= $this->maxFloated) {
            $this->maxFloated = $pl;
            $this->save();
        }

        /*
         * update the minFloated
         */
        if ($this->trailing && $pl < 0) {
            if ($pl > $this->minFloated) {
                $this->minFloated = $pl;
                $this->save();

            }
        }


        /*
         * updates the isTrailing
         */
        if (!$this->trailing) {
            if ($this->inProfit()) {
                // profit
                if ($this->getPL() > $this->takeProfit) {
                    $this->trailing = true;
                    $this->save();
//                    Event::create([
//                        'type' => 'info',
//                        'message', 'trailing take profit activated for ' . $this->symbol,
//                    ]);
                }
            } else {
                // loss
                if (abs($this->getPL()) > $this->stopLoss) {
                    $this->trailing = true;
                    $this->minFloated = $this->getPL();
                    $this->save();
//                    Event::create([
//                        'type' => 'info',
//                        'message', 'trailing stoploss activated for ' . $this->symbol,
//                    ]);
                }
            }
        } /*
         * watch for trailing P/L
         */
        else {

            if ($this->inProfit()) {
                $diff = $this->maxFloated - $this->getPL();
                // profit
                if ($diff > $this->trailingTakeProfit) {
                    self::sell($this->symbol, $this->origQty, $this->id, 'TTP');
                }
            } else {
                // loss
//                if ($this->maxFloated >= 0) {
//                    $this->trailing = false;
//                    $this->minFloated = 0;
                //just got reversed to loss from profit
//                    $this->maxFloated = $this->getPL();
//                    $this->save();
//                } else {
                if (($this->minFloated - $this->trailingStopLoss) > $this->getPL()) {
                    self::sell($this->symbol, $this->origQty, $this->id, 'TSL');
                }
//                }
            }

        }

    }

}