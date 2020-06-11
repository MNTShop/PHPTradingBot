<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/28/18
 * Time: 17:45
 */

namespace App;



use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class Brick extends Model
{
    protected $table = 'brick';
    protected $fillable = ['side', 'symbol', 'price', 'quantity', 'orderId', 'createTime','tradedNum','type'];

//    public static function getTickerHistory($symbol,$fromTime=-1)
//    {
//        $since = Carbon::createFromTimestamp($fromTime)->toDateTimeString();
//        $tickerHistory = self::where('created_at', '>', $since)
//            ->where('symbol', $symbol)
//            ->orderBy('created_at', 'desc')
//            ->get();
//            return $tickerHistory;
//    }


    public static function destroyBrickByOrderId($orderId){
        $order = self::where('orderId', $orderId)->delete();
    }

    public static function getFirstGreen($symbol){
        $order = self::where('symbol', $symbol)
            ->where('side','buy')
            ->where('')
            ->get();
    }

    public static function getAllBricksOrderId($symbol){
        $Bricks =  self::where('symbol',$symbol)->get();
        $orderIds = [];
        foreach ($Bricks as $brick){
            $orderIds[] =   $brick->orderId ;
        }
        return $orderIds;

    }
    public static function getAllBricksTimeCreation($symbol){
        $Bricks =  self::where('symbol',$symbol)->get();
        $orderIds = [];
        foreach ($Bricks as $brick){
            $orderIds[] =   $brick->createTime ;
        }
        return $orderIds;

    }

    public static function getRedBricks($symbol){
        return self::where('side', 'sell')
            ->where('symbol', $symbol)
            ->orderBy('side', 'desc')
            ->orderBy('price', 'desc')
            ->get();
    }
    public static function getGreenBricks($symbol){
        return self::where('side', 'buy')
            ->where('symbol', $symbol)
            ->orderBy('price', 'desc')
            ->get();
    }
//    public static function getTickerInitDataTV($symbol)
//    {
////        $since = Carbon::now()->subDays(30);
//        $tickerHistory =self::getTickerHistory($symbol);
//
////        [{ time: '2018-10-19', open: 180.34, high: 180.99, low: 178.57, close: 179.85 },]
//        $tickerHistoryConverted = [];
//        $prevTickerPrice = 0;
//
//        foreach ($tickerHistory as $ticker){
//            if($prevTickerPrice ==0){
//                $prevTickerPrice = $ticker->close;
//            }
//            $tickerHistoryConverted[] = ['time'=>$ticker->created_at->getTimestamp(),'open'=>$prevTickerPrice,'high'=> $ticker->high, 'low'=> $ticker->low, 'close'=> $ticker->close , 'value'=>$ticker->close];
//            $prevTickerPrice = $ticker->open;
////            error_log(print_r($ticker->close,1));
//        }
//            return $tickerHistoryConverted;
//    }
//    public static function getDestroyedRedBricks(){
//        $openOrders = BithumbTradeHelper::getOpenOrders();
//        foreach ($openOrders as $order){
//            if($order->type == 'limit' ){
//                //
//                $order->orderId;
//            }
//        }
//    }
}