<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" style="height: 60px; width: auto;">
        </a>
        <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4 py-2">تسجيل الدخول</a>
    </div>
</nav>

<style>
    .navbar {
        padding: 0.5rem 2rem;
        font-family: "Cairo", sans-serif;
        direction: rtl;
    }

    .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-primary {
        background: linear-gradient(45deg, var(--primary-color, #006db3), #00578f);
        border: none;
        box-shadow: 0 4px 10px rgba(0, 109, 179, 0.2);
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 109, 179, 0.3);
    }
</style>
