<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/12/18
 * Time: 02:38
 */

namespace App;


use Bg\Sdk\REST\Request\Spot\OpenOrdersRequest;
use Bg\Sdk\REST\Request\Spot\OrderListRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use Bg\Sdk\BithumbGlobalClient as BithumbClient;
use Bg\Sdk\Examples\REST\ServerTimeExample;
use Bg\Sdk\REST\Request\Spot\ConfigRequest;
use Bg\Sdk\REST\Request\Spot\TickerRequest;
use Bg\Sdk\REST\Request\Spot\PlaceOrderRequest;
use Bg\Sdk\REST\Request\Spot\SingleOrderRequest;

use Mockery\Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Ticker;

class BithumbTradeHelper
{

    public static function calcPercent($price, $percent)
    {
        return $price - (($price * $percent) / 100);
    }

    public static function getPercent($buy, $current)
    {
        if ($buy == 0 || $current == 0)
            return null;
        return (($current - $buy) * 100) / $current;
    }

    public static function maxPercent($current, $max, $buyPrice)
    {
        $_temp = $max / 100;
        $maxPrice = ($buyPrice * $_temp) + $buyPrice;
        return round(self::getPercent($maxPrice, $current), 2);

//        return (($current - ($buy + ($buy * $max))) * 100) / ($buy + ($buy * $max));
    }

    public static function market2symbol($market)
    {
        if (strpos($market, '-') !== false) {
            return $market;
        }
        $parts = explode('-', $market);
        return $parts[1] . $parts[0];
    }


    public static function getRIO(Order $order)
    {
        $pl = $order->getPL(true);
        $quantity = $order->origQty;

        return $quantity * $pl / 100;
    }

    public static function recentlyTradedPairs(Carbon $time)
    {
        $pairs = [];
        $orders = Order::whereHas('sellOrder')
            ->where('created_at', '>=', $time)
            ->get();
        if ($orders->isEmpty())
            return false;
        foreach ($orders as $order) {
            if (!in_array($order->symbol, $pairs)) {
                $pairs[$order->symbol] = [
                    'symbol' => $order->symbol,
                    'avpl' => round($order->getPL(true), 3)
                ];
            } else {
                $pairs[$order->symbol] = [
                    'symbol' => $order->symbol,
                    'avpl' => round($pairs[$order->symbol]['avpl'] + $order->getPL(true), 3)
                ];
            }
        }
        $collection = collect($pairs);
        return $collection->sortBy(function ($pair) {
            return $pair['avpl'];
        });
    }


    /**
     * Return REST Api object
     * @return BithumbClient
     */
    public static function getBithumb(){
        $bithumbConfig = Setting::getValue('bithumb');
        if(empty($bithumbConfig['api']) || empty($bithumbConfig['secret']) ){
            $bithumbConfig['api']='';
            $bithumbConfig['secret']='';
        }
        $bithumb = new BithumbClient($bithumbConfig['api'], $bithumbConfig['secret']);
        return $bithumb;
    }



    public static function getOpenOrdersId(BithumbClient $client,$symbol='BIP-USDT'){
        $page='';
        $count='100';
        $orderIds = [];

        if($client->getResponse(new OpenOrdersRequest($symbol,$page,$count))->isError()){

            error_log('open Orders errors  : '.PHP_EOL.print_r($symbol));
            error_log('open Orders errors  : '.PHP_EOL.print_r($page,1));
            error_log('open Orders errors  : '.PHP_EOL.print_r($count,1));
            error_log('open Orders errors  : '.PHP_EOL.print_r($client->response->getCode(),1).$client->response->getMessage());
            return $orderIds;
        }else{
            foreach ($client->response->getData()->list as $order){
                $orderIds [] = $order->orderId;
            }
            return $orderIds;
        }
    }
    public static function getOpenOrdersCreationTime(BithumbClient $client,$symbol='BIP-USDT'){
        $page='';
        $count='100';
        if($client->getResponse(new OpenOrdersRequest($symbol,$page,$count))->isError()){

            error_log('open Orders errors  : '.PHP_EOL.print_r($symbol));
            error_log('open Orders errors  : '.PHP_EOL.print_r($page,1));
            error_log('open Orders errors  : '.PHP_EOL.print_r($count,1));
            error_log('open Orders errors  : '.PHP_EOL.print_r($client->response->getCode(),1).$client->response->getMessage());
            return false;
        }else{
            $orderIds = [];
            foreach ($client->response->getData()->list as $order){
                $orderIds [] = $order->createTime;
            }
            return $orderIds;
        }
    }

    /**
     * Return Api Response or false if no param
     */
    public static function placeOrder($type=false,$symbol=false,$side=false,$price=false,$quantity=false){
        if ($type&&$symbol&&$side&&$price&&$quantity){
            $symbolConfig = self::getNotions($symbol);
//            //need to convert quantity to
//            if($side=='sell'){
//                $request->quantity =  self::calcUSDT($quantity,$symbol);
//            }
//            else{$request->quantity = $quantity; }// eg.BIP-USDT,normally point at the quantity of BIP,when type=market,side=buy,point at the quantity of USDT, and the quantity should greater than or equal to the minimum trading volume of BIP
//                error_log($bithumbConfig['api'].$bithumbConfig['secret']);
            $timestamp = ServerTimeExample::getTimestamp();
                $bithumb = self::getBithumb();
                if($bithumb->getResponse(new PlaceOrderRequest(
                    $symbol,
                    $type,
                    $side,
                    $price,
                    number_format($quantity, $symbolConfig->accuracy[1],'',''),
                    $timestamp))
                    ->isError()){
                    error_log('placeOrder : '.PHP_EOL.print_r($bithumb->response->getMessage(),1));
                    error_log('placeOrderR : '.PHP_EOL.print_r($bithumb->request,1));
                    return false;
                }else{
                    return $bithumb->response->getData();
                }
        }
        else{
            return false;
        }
    }/**
     * Return Api Response or false if no param
     */
    public static function singleOrder($orderId=false,$symbol=false){
        if ($orderId&&$symbol){

                $bithumb = self::getBithumb();
                if(!$bithumb->getResponse(new SingleOrderRequest($orderId,$symbol))->isError()){
                    error_log('singleOrder  '.PHP_EOL.print_r($bithumb->response->getData(),1));
                    return $bithumb->response->getData();
                }else{
                    error_log('singleOrder Request: '.PHP_EOL.print_r($bithumb->request,1));
                    error_log('singleOrder Error: '.PHP_EOL.print_r($bithumb->response,1));
                    return false;

                }
        }
        else{
            return false;
        }
    }

    public static function calcUSDT($amount, $symbol)
    {
        $symbol = explode('-',$symbol)[0];
        if ($price = self::getPrice($symbol . '-USDT')) {
            $usdtPrice = $price;
        } else {
            $btcPrice = self::getPrice('BTC-USDT');
            $symbol2btc = self::getPrice($symbol . '-USDT');

            $usdtPrice = $symbol2btc * $btcPrice;
        }
        return $amount / $usdtPrice;
    }

    public static function getNotions($filter = null)
    {
        $notions = [];
        if (Cache::has('notions')) {
            $notions = Cache::get('notions');
        } else {
            $bithumb = self::getBithumb();
            if(!$bithumb->getResponse(new ConfigRequest())->isError()){
                foreach ($bithumb->response->getData()->spotConfig as $symbol) {
                    $notions[$symbol->symbol] = $symbol;
                }
            }
            Cache::put('notions', $notions, now()->addMinutes(30));
        }
        if ($filter) {
            return $notions[$filter];
        }
        return $notions;
    }

//    public static function getStepSize($symbol)
//    {
//        $notion = self::getNotions($symbol);
//        if (isset($notion['filters'])) {
//            foreach ($notion['filters'] as $filter) {
//                if (isset($filter['stepSize']))
//                    return $filter['stepSize'];
//            }
//        }
//        return 0.01;
//    }

    public static function getTick($symbol)
    {
        $tick = Cache::get($symbol);
        if (empty($tick)) {
            // get last data for symbol
                $bithumb = self::getBithumb();
                if(!$bithumb->getResponse(new TickerRequest($symbol))->isError()){
                    $tick = ['symbol' => $symbol, 'close' => $bithumb->response->getData()[0]->c];
                }else{
                    return false;
                }

//            $newTicker = new Ticker();
//            $newTicker->eventType = $tick['topic'];
//            $newTicker->eventTime = $tick['timestamp'];
//            $newTicker->symbol = $tick['symbol'];
//            $newTicker->priceChange = floatval($tick['priceChange']);
//            $newTicker->high = floatval($tick['high']);
//            $newTicker->low = floatval($tick['low']);
//            $newTicker->close = floatval($tick['close']);
//            $newTicker->numTrades = intval($tick['numTrades']);

            Cache::put($symbol, $tick, now()->addSeconds(5));
        }
        return $tick;
    }

    public static function getPrice($symbol)
    {
        $tick = self::getTick($symbol);
        return $tick['close'];
    }

    public static function getSymbols()
    {
        if (Cache::has('symbols')) {
            return Cache::get('symbols');
        }
        $symbols = [];
        try {
            ob_start();
            $bithumb = self::getBithumb();
            if(!$bithumb->getResponse(new ConfigRequest())->isError()){
                $exchangeInfo = $bithumb->response->getData();
            }else{
                throw new Exception('getSymbols() Error: '.$bithumb->response->getMessage());
            }
            ob_clean();
            if (empty($exchangeInfo)) {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }

        foreach ($exchangeInfo->spotConfig as $symbol) {
            $symbols[] = $symbol->symbol;
        }
        Cache::put('symbols', $symbols, now()->addMinutes(30));
        return $symbols;
    }


    /**
     * @param $command
     * @param $service
     * @param bool $background
     * @return bool
     * @throws \Exception
     */
    public static function systemctl($service, $command, $background = true)
    {
        $output='';
        $return='';
//        $process = new Process(['/path/to/php', '--define', 'memory_limit=1024M', '/path/to/script.php']);
//$process->setWorkingDirectory();
        if ($service == 'all') {
            self::systemctl('ticker', $command);
            self::systemctl('orders', $command);
            self::systemctl('signal', $command);
            sleep(2);
            return true;
        }


//        $phpBinary = exec("which php");
        $process_which_php = Process::fromShellCommandline("which php");
        $process_which_php->run(null);
        $phpBinary = $process_which_php->getOutput();
        if (!$phpBinary) {
            throw new \Exception('cannot find php binary');
        }

//        $cmd = 'cd ' . base_path() . '; ';
//        error_log(print_r($cmd,1));
        switch ($command) {
            case 'status':

                $process = new Process(['/bin/bash','./bash/service_status.sh',$service]);
                $process->setWorkingDirectory(base_path());

//                $process->setWorkingDirectory(base_path().'/bash');
                $process->run();
                // executes after the command finishes
                if (!$process->isSuccessful()) {
//                    error_log('Exception ./service_status.sh\''.$service.$process->getOutput());
                    throw new ProcessFailedException($process);
                }
                if ($process->getOutput() != 0) {
//                    error_log('TRUE ./service_status.sh\''.$service.$process->getOutput());

                    return true;
                }
//                error_log('false ./service_status.sh\''.$service.$process->getOutput());
                return false;
            case 'restart':
                $stop = self::systemctl($service, 'stop');
                $start = self::systemctl($service, 'start');
                if ($start && $stop) {
//                    error_log('true ./service_restart.sh\'');
                    return true;
                }

//                error_log('false ./service_restart.sh\'');

                return false;
            case 'start':
//                $process = new Process(['php','artisan','daemon:'.$service,'&>/dev/null','&']);
                $process = new Process(['/bin/bash','./bash/service_start.sh',$service]);
                $process->setWorkingDirectory(base_path());
                $process->run();
                if (!$process->isSuccessful()) {
                    error_log('false ./service_start.sh\''.$service.$process->getErrorOutput());
                    throw new ProcessFailedException($process);
                }
//                error_log('here ./service_start.sh\''.$service.$process->getOutput());
                return $process->isSuccessful();
                break;
            case 'stop':
//                $cmd .= "kill $(ps aux | grep 'daemon:$service' | grep -v grep | awk '{print $2}')";
                $process = new Process(['/bin/bash','./bash/service_stop.sh',$service]);
                $process->setWorkingDirectory(base_path());
                $process->run();
                if (!$process->isSuccessful()) {
//                                        error_log('false ./service_stop.sh\''.$service.$process->getOutput());

                    throw new ProcessFailedException($process);
                }
//                                error_log('here ./service_stop.sh\''.$service.$process->getOutput());

                return $process->isSuccessful();

        }


        if ($background) {
//            $cmd .= ' > /dev/null 2>&1 &';
        }

//        error_log(print_r($cmd,1));

//        $process = Process::fromShellCommandline($cmd);
//        if (!$process->isSuccessful()) {
//            throw new ProcessFailedException($process);
//        }

//        error_log('END Process'.print_r($process->getOutput(),1));
//        $process->wait();
//        error_log($process->getOutput());

            // child process
//            $result = exec($cmd, $output, $return);
            return self::systemctl($service, 'status');



    }

    public static function isFavorite($symbol)
    {
        if ($user = Auth::user()) {
            $favorites = $user->favorites;
            $favorites = json_decode($favorites);
            if (in_array($symbol, $favorites)) {
                return true;
            }
        }
        return false;
    }
}