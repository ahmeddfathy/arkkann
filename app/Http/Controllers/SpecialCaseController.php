<?php

namespace App\Http\Controllers;

use App\Models\SpecialCase;
use App\Models\User;
use App\Imports\CheckInImport;
use App\Imports\CheckOutImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SpecialCaseController extends Controller
{
    private const WORK_START_TIME = '08:00:00';
    private const WORK_END_TIME = '16:00:00';

    public function index()
    {
        $specialCases = SpecialCase::with('employee')->latest()->get();
        return view('special-cases.index', compact('specialCases'));
    }

    public function create()
    {
        $employees = User::whereNotNull('employee_id')->get();
        return view('special-cases.create', compact('employees'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'check_in_file' => 'required|file|mimes:xlsx,xls',
            'check_out_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            DB::beginTransaction();

            $checkInArray = Excel::toArray(new CheckInImport, $request->file('check_in_file'));
            $checkOutArray = Excel::toArray(new CheckOutImport, $request->file('check_out_file'));

            if (empty($checkInArray[0]) || empty($checkOutArray[0])) {
                throw new \Exception('الملفات فارغة');
            }

            $checkInImport = new CheckInImport();
            Excel::import($checkInImport, $request->file('check_in_file'));

            $checkInRecords = SpecialCase::whereDate('date', now())
                ->whereNotNull('check_in')
                ->count();

            if ($checkInRecords === 0) {
                throw new \Exception('لم يتم إضافة أي سجلات حضور');
            }

            $checkOutImport = new CheckOutImport();
            Excel::import($checkOutImport, $request->file('check_out_file'));

            DB::commit();
            return redirect()->back()->with('success', 'تم استيراد البيانات بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,employee_id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string'
        ]);

        if ($request->check_in) {
            $checkIn = Carbon::createFromTimeString($request->check_in);
            $workStart = Carbon::createFromTimeString(self::WORK_START_TIME);

            if ($checkIn->gt($workStart)) {
                $lateMinutes = abs($checkIn->diffInMinutes($workStart));
            } else {
                $lateMinutes = 0;
            }
        }

        if ($request->check_out) {
            $checkOut = Carbon::createFromTimeString($request->check_out);
            $workEnd = Carbon::createFromTimeString(self::WORK_END_TIME);

            if ($checkOut->lt($workEnd)) {
                $earlyLeaveMinutes = abs($workEnd->diffInMinutes($checkOut));
            } else {
                $earlyLeaveMinutes = 0;
            }
        }

        $specialCase = SpecialCase::create([
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'late_minutes' => $lateMinutes ?? 0,
            'early_leave_minutes' => $earlyLeaveMinutes ?? 0,
            'reason' => $validated['reason']
        ]);

        return redirect()->route('special-cases.index')
            ->with('success', 'تم إضافة الحالة الخاصة بنجاح');
    }

    public function edit(SpecialCase $specialCase)
    {
        $employees = User::whereNotNull('employee_id')->get();
        return view('special-cases.edit', compact('specialCase', 'employees'));
    }

    public function update(Request $request, SpecialCase $specialCase)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,employee_id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string'
        ]);

        if ($request->check_in) {
            $checkIn = Carbon::createFromTimeString($request->check_in);
            $workStart = Carbon::createFromTimeString(self::WORK_START_TIME);

            if ($checkIn->gt($workStart)) {
                $lateMinutes = abs($checkIn->diffInMinutes($workStart));
            } else {
                $lateMinutes = 0;
            }
        }

        if ($request->check_out) {
            $checkOut = Carbon::createFromTimeString($request->check_out);
            $workEnd = Carbon::createFromTimeString(self::WORK_END_TIME);

            if ($checkOut->lt($workEnd)) {
                $earlyLeaveMinutes = abs($workEnd->diffInMinutes($checkOut));
            } else {
                $earlyLeaveMinutes = 0;
            }
        }

        $specialCase->update([
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'late_minutes' => $lateMinutes ?? $specialCase->late_minutes,
            'early_leave_minutes' => $earlyLeaveMinutes ?? $specialCase->early_leave_minutes,
            'reason' => $validated['reason']
        ]);

        return redirect()->route('special-cases.index')
            ->with('success', 'تم تحديث الحالة الخاصة بنجاح');
    }

    public function destroy(SpecialCase $specialCase)
    {
        $specialCase->delete();
        return redirect()->route('special-cases.index')
            ->with('success', 'تم حذف الحالة الخاصة بنجاح');
    }
}
