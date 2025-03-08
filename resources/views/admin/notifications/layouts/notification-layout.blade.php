@extends('layouts.app')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<link href="{{ asset('css/notifications.css') }}" rel="stylesheet">
<link href="{{ asset('css/admin/notifications.css') }}" rel="stylesheet">
<link href="{{ asset('css/admin/notification-index.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-title">@yield('page-title')</h1>
        @yield('page-actions')
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @yield('notification-content')
</div>
@endsection

@section('scripts')
<script src="https://kit.fontawesome.com/your-code.js"></script>
@stack('page-scripts')
@endsection
