@extends('layouts.app')

@section('content')
<div class="container">
  <h2>الحالات الخاصة</h2>

  <div class="card mb-4">
    <div class="card-header">
      استيراد ملفات الحضور والانصراف
    </div>
    <div class="card-body">
      <form action="{{ route('special-cases.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="form-label">ملف الحضور</label>
          <input type="file" name="check_in_file" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">ملف الانصراف</label>
          <input type="file" name="check_out_file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">استيراد</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>قائمة الحالات الخاصة</span>
      <a href="{{ route('special-cases.create') }}" class="btn btn-success">إضافة حالة جديدة</a>
    </div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>الموظف</th>
            <th>التاريخ</th>
            <th>وقت الحضور</th>
            <th>وقت الانصراف</th>
            <th>دقائق التأخير</th>
            <th>دقائق الانصراف المبكر</th>
            <th>السبب</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          @foreach($specialCases as $case)
          <tr>
            <td>{{ $case->employee->name }}</td>
            <td>{{ $case->date->format('Y-m-d') }}</td>
            <td>{{ $case->check_in }}</td>
            <td>{{ $case->check_out }}</td>
            <td>{{ $case->late_minutes }}</td>
            <td>{{ $case->early_leave_minutes }}</td>
            <td>{{ $case->reason }}</td>
            <td>
              <a href="{{ route('special-cases.edit', $case) }}" class="btn btn-sm btn-primary">تعديل</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection