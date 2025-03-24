@extends('layouts.app')

@section('content')

<head>
    <link rel="stylesheet" href="{{asset('css/dashboardPage.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</head>



<div class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-60">
            <div class="col-lg-6" data-aos="fade-right">
                
                <h1 class="display-4 fw-bold mb-4">نظام أركان لإدارة الحضور والإجازات</h1>
                <p class="lead mb-4">نظام متكامل لإدارة الحضور والانصراف، الإجازات، والأذونات مع تقارير تفصيلية للموظفين</p>
                <div class="hero-buttons">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg mb-4 me-3">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg mb-4">حساب جديد</a>
                </div>
            </div>
            <div class="col-lg-6 d-flex justify-content-center align-items-center" data-aos="fade-left">
                <div class="hero-image">
                    <img src="{{asset('assets/images/arkan.png')}}" alt="نظام الحضور" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</div>

<section class="features-section py-5">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">مميزات النظام</h2>
        <div class="row g-4">

            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3>طلب الإجازات</h3>
                    <p>نظام متكامل لطلب وإدارة الإجازات والأذونات</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h3>تقارير تفصيلية</h3>
                    <p>تقارير شاملة للحضور والغياب والإجازات</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-card">
                    <div class="icon-wrapper">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h3>العمل الإضافي</h3>
                    <p>إدارة وتتبع ساعات العمل الإضافي للموظفين</p>
                </div>
            </div>
        </div>
    </div>
</section>



@endsection
