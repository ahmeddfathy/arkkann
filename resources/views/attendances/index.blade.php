@extends('layouts.app')
@section('content')
<div class="attendance-section py-5">
    <div class="container">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white p-4">
                <h3 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i> سجلات الحضور
                </h3>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="{{ route('attendances.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> إضافة حضور جديد
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الموظف</th>
                                <th>وقت الحضور</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->id }}</td>
                                <td>{{ $attendance->user->name }}</td>
                                <td>{{ Carbon\Carbon::parse($attendance->check_in_time)->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('attendances.show', $attendance->id) }}" class="btn btn-info btn-sm">
                                            <i class="bi bi-eye"></i> عرض
                                        </a>
                                        <form action="{{ route('attendances.destroy', $attendance->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                                <i class="bi bi-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $attendances->links() }}
            </div>
        </div>
    </div>
</div>
@endsection


