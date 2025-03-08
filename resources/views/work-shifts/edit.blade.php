<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تعديل وردية') }}: {{ $workShift->name }}
        </h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/work-shifts.css') }}">
    @endpush

    <div class="py-12 work-shifts-container">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="work-shifts-card">
                <div class="work-shifts-header">
                    <h3 class="work-shifts-title">تعديل وردية: {{ $workShift->name }}</h3>
                </div>

                <form action="{{ route('work-shifts.update', $workShift) }}" method="POST" class="shift-form">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name" class="form-label">{{ __('اسم الوردية') }}</label>
                        <input id="name" class="form-input" type="text" name="name" value="{{ old('name', $workShift->name) }}" required autofocus />
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="check_in_time" class="form-label">{{ __('وقت الحضور') }}</label>
                        <input id="check_in_time" class="form-input" type="time" name="check_in_time" value="{{ old('check_in_time', $workShift->check_in_time->format('H:i')) }}" required />
                        @error('check_in_time')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="check_out_time" class="form-label">{{ __('وقت الانصراف') }}</label>
                        <input id="check_out_time" class="form-input" type="time" name="check_out_time" value="{{ old('check_out_time', $workShift->check_out_time->format('H:i')) }}" required />
                        @error('check_out_time')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="form-checkbox-wrapper">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1" class="form-checkbox" {{ $workShift->is_active ? 'checked' : '' }}>
                            <span class="form-label">{{ __('نشطة') }}</span>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <a href="{{ route('work-shifts.index') }}" class="cancel-btn">
                            {{ __('إلغاء') }}
                        </a>
                        <button type="submit" class="submit-btn">
                            {{ __('حفظ التغييرات') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
