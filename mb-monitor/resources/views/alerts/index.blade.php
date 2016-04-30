@extends('layout')

@section('header')

@stop

@section('content')

            <div class="title">Alerts</div>
            <table>
                @if (empty($alerts))
                    <tr><td>Nothing to report on.</td></tr>
                @else
                    @foreach ($alerts as $alert)
                        <tr><td>{{ $alert->stat_id }}</td></tr>
                    @endforeach
                @endif
            </table>

@stop

@section('footer')

@stop
