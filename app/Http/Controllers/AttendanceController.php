<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $attendances = Attendance::with('user')->latest()->paginate(10);
        return view('attendances.index', compact('attendances'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $users = User::all();
        return view('attendances.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'work_type' => 'required|in:office,remote,hybrid',
            'check_in_time' => 'required|array',
            'check_in_time.*' => [
                'required',
                'date',
                'date_format:Y-m-d\TH:i',
                function ($attribute, $value, $fail) {
                    $checkInTime = Carbon::parse($value)->setTimezone('Africa/Cairo');
                    $now = Carbon::now('Africa/Cairo');

                    if ($checkInTime->isPast() && $checkInTime->diffInHours($now) > 24) {
                        $fail('لا يمكن تسجيل الحضور لتاريخ في الماضي.');
                    }
                },
            ],
        ], [
            'user_id.required' => 'يجب اختيار الموظف',
            'user_id.exists' => 'الموظف غير موجود',
            'work_type.required' => 'يجب اختيار نوع العمل',
            'work_type.in' => 'نوع العمل غير صحيح',
            'check_in_time.required' => 'يجب إدخال موعد الحضور',
            'check_in_time.array' => 'صيغة مواعيد الحضور غير صحيحة',
            'check_in_time.*.required' => 'موعد الحضور مطلوب',
            'check_in_time.*.date' => 'يجب أن يكون موعد الحضور تاريخاً صحيحاً',
            'check_in_time.*.date_format' => 'صيغة التاريخ غير صحيحة'
        ]);

        try {
            DB::beginTransaction();

            $duplicates = [];
            foreach ($request->check_in_time as $checkInTime) {
                $date = Carbon::parse($checkInTime)->setTimezone('Africa/Cairo');

                $existingAttendance = Attendance::where('user_id', $request->user_id)
                    ->whereDate('check_in_time', $date->toDateString())
                    ->first();

                if ($existingAttendance) {
                    $duplicates[] = $date->format('Y-m-d');
                    continue;
                }

                Attendance::create([
                    'user_id' => $request->user_id,
                    'check_in_time' => $date,
                    'work_type' => $request->work_type
                ]);
            }

            DB::commit();

            if (count($duplicates) > 0) {
                $message = 'تم تسجيل الحضور بنجاح، ولكن تم تخطي التواريخ التالية لوجود تسجيل سابق: ' . implode(', ', $duplicates);
                return redirect()->route('attendances.index')
                    ->with('warning', $message);
            }

            return redirect()->route('attendances.index')
                ->with('success', 'تم تسجيل الحضور بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء تسجيل الحضور');
        }
    }

    public function show($id)
    {
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بعرض سجل الحضور');
        }

        $attendance = Attendance::with('user')->findOrFail($id);
        return view('attendances.show', compact('attendance'));
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بحذف سجل الحضور');
        }

        $attendance = Attendance::findOrFail($id);
        $attendance->delete();
        return redirect()->route('attendances.index')->with('success', 'تم حذف سجل الحضور بنجاح');
    }

    public function preview($employee_id)
    {
        try {
            $user = User::where('employee_id', $employee_id)->firstOrFail();

            if (auth()->user()->id !== $user->id && !auth()->user()->hasRole('hr')) {
                abort(403, 'غير مصرح لك بعرض هذا السجل');
            }

            $attendanceRecords = AttendanceRecord::where('employee_id', $employee_id)
                ->orderBy('date', 'desc')
                ->paginate(10);

            return view('attendance.preview', compact('user', 'attendanceRecords'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء عرض سجلات الحضور');
        }
    }
}
