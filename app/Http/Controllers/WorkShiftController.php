<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkShiftController extends Controller
{
    /**
     * عرض قائمة الورديات
     */
    public function index()
    {
        $workShifts = WorkShift::all();
        return view('work-shifts.index', compact('workShifts'));
    }

    /**
     * عرض نموذج إنشاء وردية جديدة
     */
    public function create()
    {
        return view('work-shifts.create');
    }

    /**
     * حفظ وردية جديدة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'is_active' => 'required|boolean',
        ]);

        // تحويل is_active إلى قيمة منطقية
        $validated['is_active'] = (bool)$validated['is_active'];

        WorkShift::create($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم إنشاء الوردية بنجاح.');
    }

    /**
     * عرض وردية محددة
     */
    public function show(WorkShift $workShift)
    {
        return view('work-shifts.show', compact('workShift'));
    }

    /**
     * عرض نموذج تعديل وردية
     */
    public function edit(WorkShift $workShift)
    {
        return view('work-shifts.edit', compact('workShift'));
    }

    /**
     * تحديث وردية محددة
     */
    public function update(Request $request, WorkShift $workShift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'is_active' => 'required|boolean',
        ]);

        // تحويل is_active إلى قيمة منطقية
        $validated['is_active'] = (bool)$validated['is_active'];

        $workShift->update($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم تحديث الوردية بنجاح.');
    }

    /**
     * حذف وردية محددة
     */
    public function destroy(WorkShift $workShift)
    {
        $workShift->delete();

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم حذف الوردية بنجاح.');
    }

    /**
     * تغيير حالة الوردية (نشطة/غير نشطة)
     */
    public function toggleStatus(WorkShift $workShift)
    {
        $workShift->is_active = !$workShift->is_active;
        $workShift->save();

        return redirect()->route('work-shifts.index')
            ->with('success', 'تم تغيير حالة الوردية بنجاح.');
    }
}
