@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            تعديل الحالة الخاصة
        </div>
        <div class="card-body">
            <form action="{{ route('special-cases.update', $specialCase) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">الموظف</label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">اختر الموظف</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->employee_id }}"
                            {{ $specialCase->employee_id == $employee->employee_id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">التاريخ</label>
                    <input type="date" name="date" class="form-control" value="{{ $specialCase->date->format('Y-m-d') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">وقت الحضور</label>
                    <input type="time" name="check_in" class="form-control" value="{{ $specialCase->check_in }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">وقت الانصراف</label>
                    <input type="time" name="check_out" class="form-control" value="{{ $specialCase->check_out }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">السبب</label>
                    <textarea name="reason" class="form-control" required>{{ $specialCase->reason }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
            </form>
        </div>
    </div>
</div>
@endsection