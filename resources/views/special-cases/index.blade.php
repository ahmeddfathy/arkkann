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
            <td>{{ \Carbon\Carbon::parse($case->check_in)->format('H:i') }}</td>
            <td>{{ \Carbon\Carbon::parse($case->check_out)->format('H:i') }}</td>
            <td>{{ $case->late_minutes }}</td>
            <td>{{ $case->early_leave_minutes }}</td>
            <td>{{ $case->reason }}</td>
            <td>
              <div class="btn-group" role="group">
                <a href="{{ route('special-cases.edit', $case) }}" class="btn btn-sm btn-primary">تعديل</a>
                <form action="{{ route('special-cases.destroy', $case) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                </form>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
