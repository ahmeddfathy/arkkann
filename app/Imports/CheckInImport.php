<?php

namespace App\Imports;

use App\Models\SpecialCase;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckInImport implements ToModel
{
    private const WORK_START_TIME = '08:00:00';

    public function model(array $row)
    {
        try {
            Log::info('Processing check-in row', [
                'row_data' => $row
            ]);

            // تجاهل الصف إذا كان فارغاً أو عناوين
            if (empty($row[0]) || empty($row[2]) || !is_numeric($row[2])) {
                Log::info('Skipping invalid row', [
                    'reason' => 'Empty or non-numeric data',
                    'row' => $row
                ]);
                return null;
            }

            // تحويل الرقم العشري إلى وقت
            $excelTime = floatval($row[0]);
            $totalMinutes = round($excelTime * 24 * 60);
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            // التأكد من أن الساعات لا تتجاوز 24
            $hours = $hours % 24;
            $timeString = sprintf('%02d:%02d:00', $hours, $minutes);

            Log::info('Time conversion', [
                'excel_time' => $row[0],
                'total_minutes' => $totalMinutes,
                'hours' => $hours,
                'minutes' => $minutes,
                'converted_time' => $timeString
            ]);

            // التحقق من وجود الموظف
            $employee = \App\Models\User::where('employee_id', (int)$row[2])->first();
            if (!$employee) {
                Log::error('Employee not found', [
                    'employee_id' => $row[2]
                ]);
                return null;
            }

            // التحقق من عدم وجود سجل سابق لنفس اليوم
            $existingRecord = SpecialCase::where('employee_id', (int)$row[2])
                ->whereDate('date', now())
                ->first();

            if ($existingRecord) {
                Log::info('Record already exists', [
                    'employee_id' => $row[2],
                    'date' => now()->format('Y-m-d')
                ]);
                return null;
            }

            $workStart = Carbon::createFromTimeString(self::WORK_START_TIME);
            $checkIn = Carbon::createFromTimeString($timeString);

            // حساب دقائق التأخير
            $lateMinutes = $checkIn->gt($workStart) ? abs($checkIn->diffInMinutes($workStart)) : 0;

            $specialCase = new SpecialCase([
                'employee_id' => (int)$row[2],
                'date' => now()->format('Y-m-d'),
                'check_in' => $timeString,
                'late_minutes' => $lateMinutes,
                'reason' => 'تم الاستيراد من ملف Excel',
            ]);

            Log::info('Creating check-in record', [
                'employee_id' => $row[2],
                'excel_time' => $row[0],
                'converted_time' => $timeString,
                'late_minutes' => $lateMinutes,
                'data' => $specialCase->toArray()
            ]);

            return $specialCase;
        } catch (\Exception $e) {
            Log::error('Error processing check-in row', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
