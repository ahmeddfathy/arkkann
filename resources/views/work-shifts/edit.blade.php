<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <span class="arkan-title">{{ __('تعديل وردية') }}: {{ $workShift->name }}</span>
        </h2>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Cairo:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="{{ asset('css/work-shifts-bootstrap.css') }}">
    @endpush

    <div class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">تعديل وردية: {{ $workShift->name }}</h3>
                            <div class="logo-decoration"></div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('work-shifts.update', $workShift) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('اسم الوردية') }}</label>
                                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name', $workShift->name) }}" required autofocus />
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="check_in_time" class="form-label">{{ __('وقت الحضور') }}</label>
                                    <input id="check_in_time" class="form-control" type="time" name="check_in_time" value="{{ old('check_in_time', $workShift->check_in_time->format('H:i')) }}" required />
                                    @error('check_in_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="check_out_time" class="form-label">{{ __('وقت الانصراف') }}</label>
                                    <input id="check_out_time" class="form-control" type="time" name="check_out_time" value="{{ old('check_out_time', $workShift->check_out_time->format('H:i')) }}" required />
                                    @error('check_out_time')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" {{ $workShift->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">{{ __('نشطة') }}</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('work-shifts.index') }}" class="btn btn-secondary">
                                        {{ __('إلغاء') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('حفظ التغييرات') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @endpush
</x-app-layout>
