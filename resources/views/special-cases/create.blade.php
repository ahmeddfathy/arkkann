@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            إضافة حالة خاصة جديدة
        </div>
        <div class="card-body">
            <form action="{{ route('special-cases.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">الموظف</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">اختر الموظف</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->employee_id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">التاريخ</label>
                    <input type="date" name="date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">وقت الحضور</label>
                    <input type="time" name="check_in" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">وقت الانصراف</label>
                    <input type="time" name="check_out" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">السبب</label>
                    <textarea name="reason" class="form-control" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">حفظ</button>
            </form>
        </div>
    </div>
</div>
@endsection
