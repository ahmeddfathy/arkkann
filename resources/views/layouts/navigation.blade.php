<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-1">
    <div class="container py-0">
        <a class="navbar-brand py-0" href="{{ url('/') }}">
            <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" class="arkan-logo">
        </a>
        <div class="nav-actions">
            <a href="{{ route('login') }}" class="btn btn-primary rounded-pill">تسجيل الدخول</a>
            <a href="{{ route('register') }}" class="btn btn-outline-primary rounded-pill">حساب جديد</a>
        </div>
    </div>
</nav>
