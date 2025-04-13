@extends('layouts.app')

@push('styles')
<style>
    .card {
        opacity: 1 !important;
    }
</style>
<link rel="stylesheet" href="{{ asset('css/assign-shift.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="page-title">
        <h2>Assign Work Shifts to Users</h2>
        <p class="text-muted">Assign appropriate work shifts for each employee</p>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Work Shifts Assignment Card -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-clock me-2"></i> Work Shift Management</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('users.save-work-shifts') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Shift</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                            <tr>
                                <td data-label="Username">
                                    <span class="user-name">{{ $user->name }}</span>
                                </td>
                                <td data-label="Email">
                                    <span class="user-email">{{ $user->email }}</span>
                                </td>
                                <td data-label="Department">
                                    <span class="department-badge">{{ $user->department ?? 'Not Specified' }}</span>
                                </td>
                                <td data-label="Shift">
                                    <div class="shift-options">
                                        @foreach ($workShifts as $workShift)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                id="shift_{{ $user->id }}_{{ $workShift->id }}"
                                                name="work_shifts[{{ $user->id }}]"
                                                value="{{ $workShift->id }}"
                                                {{ $user->work_shift_id == $workShift->id ? 'checked' : '' }}>
                                            <label class="form-check-label" for="shift_{{ $user->id }}_{{ $workShift->id }}">
                                                <span class="shift-type {{ strpos(strtolower($workShift->name), 'morning') !== false ? 'shift-morning' : 'shift-night' }}">
                                                    <i class="fas {{ strpos(strtolower($workShift->name), 'morning') !== false ? 'fa-sun' : 'fa-moon' }} me-2"></i>
                                                    {{ $workShift->name }} ({{ $workShift->check_in_time->format('h:i A') }} - {{ $workShift->check_out_time->format('h:i A') }})
                                                </span>
                                            </label>
                                        </div>
                                        @endforeach
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                id="shift_{{ $user->id }}_none"
                                                name="work_shifts[{{ $user->id }}]"
                                                value=""
                                                {{ !$user->work_shift_id ? 'checked' : '' }}>
                                            <label class="form-check-label" for="shift_{{ $user->id }}_none">
                                                <span class="shift-type shift-none">
                                                    <i class="fas fa-ban me-2"></i> No Shift
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-users-slash me-2"></i> No users available at the moment
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
