<div class="card">
    <div class="card-header">
        Open Positions ({{$allCount}})
    </div>

    @if($open->isNotEmpty() && $status = \App\BithumbTradeHelper::systemctl('ticker','status'))
        <table class="table table-hover table-responsive col-12">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>pom</th>
                <th>symbol</th>
                <th>Qty</th>
                <th>IsTrailing</th>
                <th>
                    graph
                </th>
                <th>note</th>
                <th>risk</th>
                <th>max</th>
                <th>min</th>
                <th>
                    Action
                </th>
            </tr>
            </thead>
            <tbody>
            @foreach($open as $symbol)

                @foreach($symbol as $order)
                    <form action="{{route('editPosition',$order->id)}}" method="post">
                        {{csrf_field()}}
                        <tr class="graphRow" data-buy="{{$order->price}}" data-pl="{{$order->getPL()}}" data-tp="{{$order->takeProfit}}" data-ttp="{{$order->trailingTakeProfit}}" data-sl="{{$order->stopLoss}}" data-tsl="{{$order->trailingStopLoss}}">
                            <td>{{$order->id}}</td>
                            <td>{{$order->created_at->diffForHumans()}}</td>
                            <td>
                                {{round($order->maxFloated - $order->getPL(),2)}}%
                            </td>
                            <td>{{$order->symbol}}</td>
                            <td>{{$order->origQty}}</td>
                            <td>
                                @if($order->trailing)
                                    <a href="{{route('toggleTrailing',$order->id)}}" class="btn btn-secondary">Yes</a>
                                @else
                                    <a href="{{route('toggleTrailing',$order->id)}}"
                                       class="btn btn-outline-secondary">No</a>
                                @endif
                            </td>
                            <td class="graph" style="width: 100%"></td>
                            <td>
                                {{$order->comment}}
                            </td>
                            <td>
                                @if($order->signal)
                                    {{$order->signal->rl}}
                                @endif
                            </td>
                            <td>{{round($order->maxFloated,4)}}%</td>
                            <td>{{round($order->minFloated,4)}}%</td>
                            <td>
                                <div class="btn-group" role="group" aria-label="Basic example">
                                    <a href="{{route('positions',$order->id)}}" class="btn btn-success">Edit</a>
                                    <a href="{{route('closePosition',$order->id)}}" class="btn btn-danger"
                                       onclick="return confirm('Are you sure?');">Close</a>
                                    <button onclick="openTV('{{$order->symbol}}')" class="btn btn-secondary">TV</button>
                                </div>
                            </td>
                        </tr>
                    </form>
                @endforeach


            @endforeach
            </tbody>
        </table>
    @else
        <p class="col-12 pt-3">
            no open order
        </p>
    @endif

    @if(isset($status) && $status != true)
        <p class="col-12 pt-3">
            Service is not Running
        </p>
    @endif
</div>