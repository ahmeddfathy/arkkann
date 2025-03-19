@extends('layouts.app')

<head>
    <link href="{{ asset('css/overtime-managment.css') }}" rel="stylesheet">
    <!-- Add Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
@section('content')
<div class="container">
    @include('shared.alerts')
    @include('overtime-requests.components._search')
    @include('overtime-requests.components._table')


</div>

    @include('overtime-requests.components._modals')
    @include('overtime-requests.components._statstics')


    @include('overtime-requests.components.chart-data')
    @include('overtime-requests.components.scripts')



@endsection