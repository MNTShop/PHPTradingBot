@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            Signals
        </div>
        <div class="card-body">
            @if($signals->isNotEmpty())
                <table class="table table-hover table-responsive col-12">
                    <thead>
                    <tr>
                        @foreach(array_keys($signals->first()->toArray()) as $column)
                            <th>{{$column}}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($signals as $signal)
                        <tr>
                            @foreach($signal->toArray() as $value)
                                <td>
                                    {{$value}}
                                </td>
                            @endforeach
                            <td>
                                <a href="{{route('showSymbol',\App\BithumbTradeHelper::market2symbol($signal->market))}}" class="btn btn-secondary">TV</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p>
                    no signal
                </p>
            @endif
            <div class="col-2 offset-5">
                {{$signals->links()}}
            </div>
        </div>
    </div>

@endsection