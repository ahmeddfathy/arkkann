@extends('layouts.app')
@section('content')
<div class="attendance-show-section py-5">
    <div class="container">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white p-4">
                <h3 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i> تفاصيل الحضور
                </h3>
            </div>
            <div class="card-body p-4">
                <table class="table">
                    <tbody>
                        <tr>
                            <th>رقم السجل</th>
                            <td>{{ $attendance->id }}</td>
                        </tr>
                        <tr>
                            <th>الموظف</th>
                            <td>{{ $attendance->user->name }}</td>
                        </tr>
                        <tr>
                            <th>وقت الحضور</th>
                            <td>{{ Carbon\Carbon::parse($attendance->check_in_time)->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ الإنشاء</th>
                            <td>{{ $attendance->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>آخر تحديث</th>
                            <td>{{ $attendance->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('attendances.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i> رجوع
                    </a>
                    <form action="{{ route('attendances.destroy', $attendance->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">
                            <i class="bi bi-trash"></i> حذف السجل
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


