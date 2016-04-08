@extends('layout')

@section('header')

@stop

@section('content')

            <div class="title">Alerts</div>
            <table>
                @if (empty($stats))
                    <tr><td>Nothing to report on.</td></tr>
                @else
                    @foreach ($stats as $stat)
                        <tr><td>{{ $stat }}</td></tr>
                    @endforeach
                @endif
            </table>

@stop

@section('footer')

@stop
