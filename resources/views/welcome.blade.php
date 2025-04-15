@extends('layouts.app')

@section('htmldir', 'rtl')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/homePage.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="home-wrapper">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-6 hero-text" data-aos="fade-right" data-aos-duration="1000">
                    <h1>نظام إدارة الموارد البشرية المتكامل</h1>
                    <p class="hero-description">إدارة الإجازات، الأذونات، العمل الإضافي، وتحليل الأداء في منصة واحدة سهلة الاستخدام</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="btn-primary">تسجيل الدخول</a>
                        <a href="{{ route('register') }}" class="btn-secondary">حساب جديد</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <img src="{{ asset('assets/images/arkan.png') }}" alt="شركة أركان">
                    <div class="shape-1"></div>
                    <div class="shape-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-header" data-aos="fade-up" data-aos-duration="800">
                        <h2>خدمات النظام</h2>
                        <p>كل ما تحتاجه لإدارة فريق العمل في مكان واحد</p>
                    </div>
                </div>
            </div>

            <div class="row feature-cards">
                <!-- Feature 1 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="icon-container">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>إدارة الإجازات</h3>
                        <p>تقديم وإدارة طلبات الإجازة مع متابعة رصيد الإجازات واعتمادها</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="icon-container">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>الأذونات والمغادرات</h3>
                        <p>طلب أذونات المغادرة والتأخير مع نظام موافقات سلس</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="icon-container">
                            <i class="fas fa-business-time"></i>
                        </div>
                        <h3>العمل الإضافي</h3>
                        <p>تسجيل وإدارة ساعات العمل الإضافي مع حساب تلقائي للمستحقات</p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="icon-container">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>تحليل الأداء</h3>
                        <p>متابعة أداء الموظفين مع تقارير تفصيلية ومؤشرات قياس الأداء</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-header" data-aos="fade-up" data-aos-duration="800">
                        <h2>إحصائيات النظام</h2>
                        <p>نتائج استخدام نظام أركان للموارد البشرية</p>
                    </div>
                </div>
            </div>

            <div class="row stats-cards">
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-duration="800">
                    <div class="stat-card">
                        <h3>+50%</h3>
                        <p>تحسين في كفاءة إدارة الموارد البشرية</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                    <div class="stat-card">
                        <h3>-30%</h3>
                        <p>تقليل الوقت المستغرق في الإجراءات الإدارية</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    <div class="stat-card">
                        <h3>+80%</h3>
                        <p>رضا الموظفين عن سهولة استخدام النظام</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <div class="container">
            <div class="cta-content" data-aos="fade-up" data-aos-duration="800">
                <h2>جاهز للبدء؟</h2>
                <p>قم بالتسجيل الآن واستمتع بإدارة أفضل لفريق العمل</p>
                <a href="{{ route('register') }}" class="btn-primary">إنشاء حساب</a>
            </div>
        </div>
    </div>
</div>

<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            once: true,
            mirror: true,
            offset: 50
        });
    });
</script>
@endsection
