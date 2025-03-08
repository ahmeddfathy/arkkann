<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تعيين الورديات للمستخدمين') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('users.save-work-shifts') }}" method="POST">
                    @csrf

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        اسم المستخدم
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        البريد الإلكتروني
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        القسم
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الوردية
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->department ?? 'غير محدد' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col space-y-2">
                                                @foreach ($workShifts as $workShift)
                                                    <label class="inline-flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="work_shifts[{{ $user->id }}]"
                                                            value="{{ $workShift->id }}"
                                                            {{ $user->work_shift_id == $workShift->id ? 'checked' : '' }}
                                                            class="form-radio h-4 w-4 text-indigo-600 transition duration-150 ease-in-out"
                                                        >
                                                        <span class="mr-2 text-gray-700">{{ $workShift->name }} ({{ $workShift->check_in_time->format('h:i A') }} - {{ $workShift->check_out_time->format('h:i A') }})</span>
                                                    </label>
                                                @endforeach
                                                <label class="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        name="work_shifts[{{ $user->id }}]"
                                                        value=""
                                                        {{ !$user->work_shift_id ? 'checked' : '' }}
                                                        class="form-radio h-4 w-4 text-gray-600 transition duration-150 ease-in-out"
                                                    >
                                                    <span class="mr-2 text-gray-700">بدون وردية</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            لا يوجد مستخدمين
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end mt-6">
                        <x-button>
                            {{ __('حفظ التغييرات') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
