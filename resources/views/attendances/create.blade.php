@extends('layouts.app')
@section('content')
<div class="card mt-5">
    <div class="card-header bg-primary text-white">
        <h3>تسجيل حضور جديد</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('attendances.store') }}" method="POST" onsubmit="return validateDates()">
            @csrf

            <!-- اختيار الموظف -->
            <div class="form-group mb-3">
                <label for="user_id">اختر الموظف</label>
                <select name="user_id" id="user_id" class="form-control">
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- نوع العمل -->
            <div class="form-group mb-3">
                <label for="work_type">نوع العمل</label>
                <select name="work_type" id="work_type" class="form-control" onchange="handleWorkTypeChange(this.value)">
                    <option value="office">من المكتب</option>
                    <option value="remote">عن بعد</option>
                    <option value="hybrid">هجين</option>
                </select>
            </div>

            <!-- إضافة حقل اختيار تاريخ بداية الأسبوع -->
            <div class="form-group mb-3" id="week_start_container" style="display: none;">
                <label for="week_start">تاريخ بداية الأسبوع</label>
                <input type="date" id="week_start" class="form-control" onchange="updateWeekDays()">
            </div>

            <!-- اختيار التواريخ -->
            <div class="form-group mb-3">
                <label>مواعيد الحضور</label>
                <div id="dates_container" class="mt-2">
                    <!-- سيتم إضافة حقول التواريخ هنا ديناميكياً -->
                </div>
                <button type="button" onclick="addDateField()" class="btn btn-success mt-2">
                    <i class="fas fa-plus"></i> إضافة موعد حضور
                </button>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">حفظ الحضور</button>
                <a href="{{ route('attendances.index') }}" class="btn btn-secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>

<script>
    let dateFieldCount = 0;
    const weekDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];

    function getEgyptDateTime(date) {
        const egyptTime = new Date(date.toLocaleString('en-US', {
            timeZone: 'Africa/Cairo'
        }));
        const year = egyptTime.getFullYear();
        const month = String(egyptTime.getMonth() + 1).padStart(2, '0');
        const day = String(egyptTime.getDate()).padStart(2, '0');
        const hours = String(egyptTime.getHours()).padStart(2, '0');
        const minutes = String(egyptTime.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function addDateField() {
        const container = document.getElementById('dates_container');
        const fieldId = `check_in_time_${dateFieldCount}`;

        const dateField = document.createElement('div');
        dateField.className = 'input-group mb-2';
        dateField.innerHTML = `
            <input type="datetime-local" name="check_in_time[]" id="${fieldId}"
                class="form-control" required>
            <button type="button" onclick="removeDateField(this)"
                class="btn btn-danger">
                <i class="fas fa-trash"></i>
            </button>
        `;

        container.appendChild(dateField);

        // تعيين التاريخ الحالي بتوقيت مصر
        const now = new Date();
        document.getElementById(fieldId).value = getEgyptDateTime(now);

        dateFieldCount++;
    }

    function removeDateField(button) {
        button.closest('.input-group').remove();
    }

    function handleWorkTypeChange(workType) {
        const container = document.getElementById('dates_container');
        const weekStartContainer = document.getElementById('week_start_container');
        container.innerHTML = '';

        if (workType === 'remote' || workType === 'hybrid') {
            // إظهار حقل اختيار تاريخ بداية الأسبوع
            weekStartContainer.style.display = 'block';

            // الحصول على تاريخ بداية الأسبوع الحالي (السبت)
            const today = new Date();
            const currentDay = today.getDay();
            // حساب عدد الأيام للرجوع للسبت السابق
            const daysToLastSaturday = currentDay === 6 ? 0 : currentDay + 1;
            const currentWeekStart = new Date(today);
            currentWeekStart.setDate(today.getDate() - daysToLastSaturday);

            // تنسيق التاريخ للـ input
            const formattedDate = currentWeekStart.toISOString().split('T')[0];
            document.getElementById('week_start').value = formattedDate;

            // إضافة الأيام
            updateWeekDays();
        } else {
            // إخفاء حقل اختيار تاريخ بداية الأسبوع
            weekStartContainer.style.display = 'none';
            // إضافة حقل تاريخ واحد افتراضي للعمل من المكتب
            addDateField();
        }
    }

    function updateWeekDays() {
        const container = document.getElementById('dates_container');
        const weekStart = new Date(document.getElementById('week_start').value);
        container.innerHTML = '';

        // التأكد من أن التاريخ المحدد هو يوم سبت
        if (weekStart.getDay() !== 6) { // 6 يمثل يوم السبت
            alert('الرجاء اختيار يوم سبت كبداية للأسبوع');
            return;
        }

        // ضبط الوقت على 8 صباحاً
        weekStart.setHours(8, 0, 0, 0);

        for (let i = 0; i < 6; i++) {
            const date = new Date(weekStart);
            date.setDate(weekStart.getDate() + i);

            const fieldId = `check_in_time_${dateFieldCount}`;
            const dateField = document.createElement('div');
            dateField.className = 'input-group mb-2';
            dateField.innerHTML = `
            <span class="input-group-text" style="width: 100px;">${weekDays[i]}</span>
            <input type="datetime-local" name="check_in_time[]" id="${fieldId}"
                value="${getEgyptDateTime(date)}"
                class="form-control" required>
            <button type="button" onclick="removeDateField(this)"
                class="btn btn-danger">
                <i class="fas fa-trash"></i>
            </button>
        `;

            container.appendChild(dateField);
            dateFieldCount++;
        }
    }

    // إضافة حقل تاريخ افتراضي عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        addDateField();
    });

    function validateDates() {
        const inputs = document.querySelectorAll('input[name="check_in_time[]"]');
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);

        for (let input of inputs) {
            const checkInTime = new Date(input.value);

            if (isNaN(checkInTime.getTime())) {
                alert('الرجاء إدخال تاريخ صحيح');
                input.focus();
                return false;
            }

            if (checkInTime < yesterday) {
                alert('لا يمكن تسجيل الحضور لتاريخ في الماضي');
                input.focus();
                return false;
            }
        }

        return true;
    }
</script>
@endsection