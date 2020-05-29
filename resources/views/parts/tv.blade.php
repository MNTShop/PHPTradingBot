<div class="card mt-2">
    <div class="card-header">
        <span class="text-left tradingSymbol">

        </span>
        <span class="text-right">
            <i class="fa toggleFavorite"></i>
        </span>
    </div>
    <style>
        .tradingview-widget-container {

        }

        #tradingview_b42a6 {
            width: 100%;
            min-height: 600px;
            height: 600px;
        }
        #tv-lightweight-charts {
            width: 100%;
            min-height: 600px;
            height: 600px;
        }
    </style>

    <div class="tradingview-widget-container">
        <div id="tradingview_b42a6"></div>
        {{--<script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>--}}
        {{--<script type="text/javascript">--}}

            {{--var edit = {{isset($order) ? 1 : 0}};--}}
            {{--var show = {{isset($show) ? 1 : 0}};--}}
                    {{--@if($show)--}}
            {{--var symbol = "{{$symbol}}";--}}
                    {{--@else--}}
            {{--var symbol = "{{isset($order) ? $order->symbol : ''}}";--}}

            {{--@endif--}}
            {{--$(document).ready(function () {--}}
                {{--if (edit || show) {--}}
                    {{--openTV(symbol);--}}
                    {{--$("#pair").val(symbol);--}}
                    {{--if (symbol == "") {--}}
                        {{--openTV('BTCUSDT');--}}
                    {{--}--}}
                {{--}--}}
            {{--});--}}


        {{--</script>--}}
        <script type="text/javascript" src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
        <script type="text/javascript">

            var edit = {{isset($order) ? 1 : 0}};
            var show = {{isset($show) ? 1 : 0}};
            {{--var data = {{isset($symbolHistory) ? echo json_encode($symbolHistory) : 0}};--}}
            var symbolHistory = {!!isset($symbolHistory) ? json_encode($symbolHistory) : 0!!};
                    @if($show)
            var symbol = "{{$symbol}}";
                    @else
            var symbol = "{{isset($order) ? $order->symbol : 'BIP-USDT'}}";

            @endif
            $(document).ready(function () {
                if (edit || show) {
                    openTV(symbol);
                    $("#pair").val(symbol);
                    if (symbol == "") {
                        openTV('BIP-USDT');
                    }

                }
            });

        </script>
    </div>
</div>