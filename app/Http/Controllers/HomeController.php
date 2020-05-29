<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/11/18
 * Time: 22:55
 */

namespace App\Http\Controllers;


use App\Modules;
use App\Order;
use App\Setting;
use App\Signal;
use App\BithumbTradeHelper;
use App\Ticker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('index');
    }

    public function signals()
    {
        return view('pages.signals', [
            'signals' => Signal::orderBy('created_at', 'desc')->paginate(10)
        ]);
    }

    public function system()
    {
        $bithumbConfig = Setting::getValue('bithumb');

        $lastTick = Cache::get('lastTick');
        $diffTicks = $lastTick ? Carbon::createFromTimestamp($lastTick)->diffInSeconds(now()) : null;
        return view('pages.system', [
            'diffTicks' => $diffTicks,
            'bithumbConfig' => $bithumbConfig,
            'tickerStatus' => BithumbTradeHelper::systemctl('ticker', 'status'),
            'ordersStatus' => BithumbTradeHelper::systemctl('orders', 'status'),
            'signalStatus' => BithumbTradeHelper::systemctl('signal', 'status'),
        ]);
    }

    public function systemCtl($command, $service)
    {
        try {
            $result = BithumbTradeHelper::systemctl($service, $command);
        } catch (\Exception $exception) {
            $result = false;
        }
        if ($result) {
            return redirect()->back()->with('success');
        }
        return redirect()->back()->withErrors('failed to ' . $command . ' ' . $service . ' service.');
    }

    public function positions($id = null)
    {
        $open = Order::getOpenPositions();
        $count = count(Order::getOpenPositions(true));
        $order = null;
        $showSymbol = false;

        if (Route::currentRouteName() == 'showSymbol') {
            $showSymbol = true;
        } else {
            if ($id) {
                $order = Order::find($id);
            }
        }


        return view('pages.positions', [
            'open' => $open,
            'allCount' => $count,
            'order' => $order,
            'show' => $showSymbol,
            'symbol' => $id,
        ]);
    }

    public function openTable()
    {
        $open = Order::getOpenPositions();
        $count = count(Order::getOpenPositions(true));

        $html = view('parts.openTable', [
            'open' => $open,
            'allCount' => $count
        ]);

        return $html;
    }

    public function history($column = 'created_at', $sortType = 'desc')
    {
        $since = Carbon::now()->subDays(30);

        // todo change to join queries

        $orders = Order::where('created_at', '>', $since)
            ->where('side', 'BUY')
            ->whereHas('sellOrder')
            ->orderBy($column, $sortType)
            ->paginate(20);
        $all = $orders;

        return view('pages.history', [
            'orders' => $orders,
            'all' => $all,
            'column' => $column,
            'sortType' => $sortType
        ]);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public
    function closePosition($id)
    {
        $buyId = Order::find($id);

        $sellOrderInfo = Order::sell($buyId->symbol, $buyId->origQty, $buyId->id);
        return redirect()->back()->with('success', 'position closed.');
    }

    /**
     * @param $id
     * @param Request $request
     * @return void
     */
    public
    function editPosition($id, Request $request)
    {
        $order = Order::find($id);

        $data = $request->except('_token');

        foreach ($data as $property => $value) {
            if ($value != '-')
                $order->{$property} = $value;
        }

        $order->save();
        return redirect()->back()->with('success', 'position modified.');
    }

    public function newPosition($type,$market, $quantity, $tp = null, $sl = null, $ttp = null, $tsl = null, Request $request)
    {
        $options = [];

        if ($tp != '-')
            $options['tp'] = $tp;
        if ($sl != '-')
            $options['sl'] = $sl;
        if ($ttp != '-')
            $options['ttp'] = $ttp;
        if ($tsl != '-')
            $options['tsl'] = $tsl;

        $symbol = BithumbTradeHelper::market2symbol($market);
        if($type == 'BUY'){
            $buyId = Order::buy($symbol, $quantity, '', $options);
        }elseif ($type=='SELL'){
            $buyId = Order::buy($symbol, $quantity, null, $options,1);
        }


        return redirect(route('positions'))->with('success', 'position opened.');
    }

    public
    function modules()
    {
        return view('pages.modules');
    }

    public
    function enableModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->setActive();
        return redirect()->back();
    }

    public
    function disableModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->setInactive();
        return redirect()->back();
    }

    public
    function installModule($moduleName)
    {
        Modules::install($moduleName);
        return redirect()->back();
    }

    public
    function uninstallModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->delete();
        return redirect()->back();
    }

    public
    function saveSettings(Request $request)
    {
        $bithumb = $request->get('bithumb');
        if (!$request->get('proxyEnabled')) {
            unset($bithumb['proxy']);
        }
        if ($request->has('orderDefaults')) {
            Setting::setValue('orderDefaults', $request->get('orderDefaults'));
        }
        if ($bithumb['api'] != null || $bithumb['api'] != null) {
            Setting::setValue('bithumb', $bithumb);
        }

        foreach ($request->all() as $key => $item) {
            if (!is_array($item) && $key != '_token') {
                Setting::setValue($key, $item);
            }
        }
        if ($request->get('trainingMode') != '1') {
            Setting::setValue('trainingMode', null);
        }
        if ($request->get('modulesCanStopOrders') != '1') {
            Setting::setValue('modulesCanStopOrders', null);
        }
        if ($request->get('saveTicker') != '1') {
            Setting::setValue('saveTicker', null);
        }

        return redirect()->back()->with('success');
    }

    public
    function saveOrderDefaults(Request $request)
    {
        $data = $request->except('_token');
        Setting::setValue('orderDefaults', $data['orderDefaults']);

        return redirect()->back();
    }


    public
    function toggleTrailing($id)
    {
        $order = Order::find($id);
        $order->trailing = !$order->trailing;
        $order->save();
        return redirect()->back();
    }

    public
    function savePosition(Request $request)
    {
        $order = Order::find($request->get('id'));
        $order->takeProfit = $request->get('tp');
        $order->stopLoss = $request->get('sl');
        $order->trailingTakeProfit = $request->get('ttp');
        $order->trailingStopLoss = $request->get('tsl');
        $order->save();
        return response('success', 200);
    }

    public function favorites()
    {
        if (isset(Auth::user()->favorites)) {
            $favorites = Auth::user()->favorites;
            if (!$favorites) {
                echo '[]';
            }
            return response(unserialize($favorites), 200);
        }
        echo '[]';
    }

    public function toggleFavorite($symbol)
    {
        $favorites = [];
        if (Auth::user()) {
            $favorites = Auth::user()->favorites;
            $favorites = unserialize($favorites);
            if (!$favorites)
                $favorites = [];
            if (!in_array($symbol, $favorites)) {
                $favorites[] = $symbol;
            } else {
                if (($key = array_search($symbol, $favorites)) !== false) {
                    unset($favorites[$key]);
                }
            }
        }
        $user = Auth::user();
        $user->favorites = serialize($favorites);
        $user->save();

        return response(unserialize($user->favorites), 200);
    }

    public function recentOrders()
    {
        $orders = Order::where('sell_date', '>', now()->subDays(7))->orderBy('sell_date','desc')->limit(20)->get();
        return $orders->toArray();
    }


}