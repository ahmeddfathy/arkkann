@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/absence-management.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">

    <!-- Display current period -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-calendar-alt me-2"></i>
        الفترة: {{ $dateStart->format('Y-m-d') }} إلى {{ $dateEnd->format('Y-m-d') }}
    </div>

    @include('absence-requests.components._search')
    @include('absence-requests.components._table')
    @include('absence-requests.components._statstics')
    @include('absence-requests.components._modals')

</div>

@if(isset($statistics))
    <!-- Statistics data element -->
<div id="absence-statistics-data" data-statistics="{{ json_encode($statistics) }}" style="display: none;"></div>
@endif

@endsection

@push('scripts')
<!-- Third-party Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="{{ asset('js/absence-requests/index.js') }}"></script>
<script src="{{ asset('js/absence-requests/common.js') }}"></script>
<script src="{{ asset('js/absence-requests/modals.js') }}"></script>
<script src="{{ asset('js/absence-requests/statistics.js') }}"></script>
<script src="{{ asset('js/absence-requests/hr-charts.js') }}"></script>

@endpush
