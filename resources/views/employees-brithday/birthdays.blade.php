@extends('layouts.app')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/birthday.css') }}">
@endpush

@section('content')
    @if(auth()->user()->hasRole('hr'))
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="birthday-container shadow-lg">
                    <!-- Confetti container -->
                    <div class="confetti-container">
                        <div class="confetti confetti-1"></div>
                        <div class="confetti confetti-2"></div>
                        <div class="confetti confetti-3"></div>
                        <div class="confetti confetti-4"></div>
                        <div class="confetti confetti-5"></div>
                        <div class="confetti confetti-6"></div>
                        <div class="confetti confetti-7"></div>
                        <div class="confetti confetti-8"></div>
                        <div class="confetti confetti-9"></div>
                    </div>

                    <!-- Decorative elements that match Arkan logo theme -->
                    <div class="birthday-decoration" style="top: 5%; left: 5%;">🎁</div>
                    <div class="birthday-decoration" style="top: 10%; right: 7%;">🎂</div>
                    <div class="birthday-decoration" style="bottom: 15%; left: 8%;">🎈</div>
                    <div class="birthday-decoration" style="bottom: 10%; right: 5%;">🎁</div>

                    <!-- Balloons -->
                    <div class="balloon" style="top: 15%; left: 10%;">🎈</div>
                    <div class="balloon" style="top: 8%; right: 15%;">🎈</div>
                    <div class="balloon" style="bottom: 30%; left: 18%;">🎈</div>
                    <div class="balloon" style="bottom: 25%; right: 12%;">🎈</div>

                    <!-- Gift boxes -->
                    <div class="gift-box" style="top: 25%; right: 8%;">🎁</div>
                    <div class="gift-box" style="bottom: 20%; left: 15%;">🎁</div>

                    <!-- Snowflake decorations to match Arkan logo -->
                    <div class="snowflake-decoration" style="top: 15%; left: 15%;">❄️</div>
                    <div class="snowflake-decoration" style="top: 20%; right: 20%;">❄️</div>
                    <div class="snowflake-decoration" style="bottom: 25%; left: 25%;">❄️</div>
                    <div class="snowflake-decoration" style="bottom: 20%; right: 30%;">❄️</div>

                    <div class="birthday-header">
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <span class="fs-1 mx-2 cake-icon">🎂</span>
                            <h1 class="fs-2 fw-bold" style="color: #4db6e2;">قائمة أعياد ميلاد الموظفين</h1>
                            <span class="fs-1 mx-2 birthday-icon">🎈</span>
                        </div>
                        <p class="text-muted">استعرض أقرب أعياد ميلاد قادمة للموظفين مرتبة حسب التاريخ</p>
                    </div>

                    <div class="p-4">
                        <div class="table-responsive">
                            <table class="table table-hover birthday-table">
                                <thead>
                                    <tr>
                                        <th class="text-end">الاسم</th>
                                        <th class="text-end">تاريخ الميلاد</th>
                                        <th class="text-end">العمر</th>
                                        <th class="text-end">عيد الميلاد القادم</th>
                                        <th class="text-end">الأيام المتبقية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employeesWithBirthdayData as $employee)
                                        <tr class="{{ $employee['is_in_same_week'] ? 'birthday-week' : '' }}">
                                            <td class="fw-medium">
                                                <div class="d-flex align-items-center">
                                                    @if($employee['is_in_same_week'])
                                                        <span class="fs-5 me-2 birthday-icon">🎉</span>
                                                    @endif
                                                    {{ $employee['name'] }}
                                                </div>
                                            </td>
                                            <td>
                                                {{ $employee['birth_date'] }}
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold" style="color: #4db6e2;">{{ $employee['age'] }}</span>
                                                    <span class="ms-1 text-muted">سنة</span>
                                                </div>
                                            </td>
                                            <td class="{{ $employee['is_in_same_week'] ? 'upcoming-birthday' : '' }}">
                                                {{ $employee['next_birthday'] }}
                                            </td>
                                            <td>
                                                <span class="days-badge">
                                                    {{ $employee['days_until_birthday'] }}
                                                    <span class="me-1">يوم</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-4 d-flex justify-content-center align-items-center gap-3 birthday-footer">
                        <span class="fs-2 cake-icon">🎂</span>
                        <span class="fs-2 snowflake-decoration" style="position: static; animation-delay: 0.5s;">❄️</span>
                        <span class="fs-2 gift-box" style="position: static; animation-delay: 1s;">🎁</span>
                        <span class="fs-2 snowflake-decoration" style="position: static; animation-delay: 1.5s;">❄️</span>
                        <span class="fs-2 balloon" style="position: static; animation-delay: 2s;">🎈</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="alert alert-danger">
                    عذراً، لا يمكنك الوصول إلى هذه الصفحة. هذه الصفحة مخصصة لموظفي الموارد البشرية فقط.
                </div>
            </div>
        </div>
    @endif
@endsection
