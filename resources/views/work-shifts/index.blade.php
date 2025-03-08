<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('إدارة الورديات') }}
        </h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/work-shifts.css') }}">
    @endpush

    <div class="py-12 work-shifts-container">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="work-shifts-card">
                <div class="work-shifts-header">
                    <h3 class="work-shifts-title">قائمة الورديات</h3>
                    <a href="{{ route('work-shifts.create') }}" class="add-shift-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        إضافة وردية جديدة
                    </a>
                </div>

                @if (session('success'))
                    <div class="success-alert" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="shifts-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>وقت الحضور</th>
                                <th>وقت الانصراف</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workShifts as $workShift)
                                <tr>
                                    <td>
                                        <div>{{ $workShift->name }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $workShift->check_in_time->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $workShift->check_out_time->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $workShift->is_active ? 'active' : 'inactive' }}">
                                            {{ $workShift->is_active ? 'نشطة' : 'غير نشطة' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('work-shifts.edit', $workShift) }}" class="action-btn edit-btn">تعديل</a>

                                            <form action="{{ route('work-shifts.toggle-status', $workShift) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="action-btn toggle-btn {{ $workShift->is_active ? 'deactivate' : 'activate' }}">
                                                    {{ $workShift->is_active ? 'تعطيل' : 'تفعيل' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('work-shifts.destroy', $workShift) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوردية؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn delete-btn">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        لا توجد ورديات مضافة حتى الآن
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
