<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class UsersImport implements ToModel, WithEvents
{
    protected $duplicates = [];
    protected $skippedRows = [];

    public function model(array $row)
    {
        if (empty($row[1]) || empty($row[4]) || empty($row[9])) {
            $this->skippedRows[] = [
                'employee_name' => $row[1] ?? 'غير متوفر',
                'reason' => 'بيانات إلزامية مفقودة (الاسم أو رقم الهاتف أو الرقم الوطني)'
            ];
            return null;
        }

        $existingUser = User::where('employee_id', $row[20])
            ->orWhere('national_id_number', $row[9])
            ->orWhere('email', $row[5])
            ->first();

        if ($existingUser) {
            $this->duplicates[] = [
                'employee_name' => $row[1],
                'employee_id' => $row[20],
                'reason' => 'الموظف موجود مسبقاً'
            ];
            return null;
        }

        $dateOfBirth = null;
        if (!empty($row[6]) && is_numeric($row[6])) {
            try {
                $dateOfBirth = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[6]))->format('Y-m-d'));
            } catch (\Exception $e) {
                $dateOfBirth = null;
            }
        }

        $startDateOfEmployment = null;
        if (!empty($row[14]) && is_numeric($row[14])) {
            $startDateOfEmployment = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[14]))->format('Y-m-d'));
        }

        $age = 0;
        if ($dateOfBirth) {
            $now = Carbon::now();
            $age = abs($now->diffInYears($dateOfBirth));
            if ($now->month < $dateOfBirth->month || ($now->month == $dateOfBirth->month && $now->day < $dateOfBirth->day)) {
                $age--;
            }
        }

        $user = new User([
            'employee_id' => isset($row[20]) && is_numeric($row[20]) ? (int)$row[20] : null,
            'name' => $row[1] ?? null,
            'gender' => $row[2] ?? null,
            'password' => bcrypt($row[9] ?? 'default_password'),
            'address' => $row[3] ?? null,
            'phone_number' => $row[4] ?? null,
            'email' => $row[5] ?? null,
            'date_of_birth' => $dateOfBirth ?? '1900-01-01',
            'national_id_number' => $row[9] ?? null,
            'education_level' => $row[10] ?? null,
            'marital_status' => $row[11] ?? null,
            'number_of_children' => isset($row[12]) && is_numeric($row[12]) ? (int)$row[12] : 0,
            'department' => $row[13] ?? null,
            'start_date_of_employment' => $startDateOfEmployment ?? '1900-01-01',
            'employee_status' => $row[18] ?? 'active',
            'last_contract_start_date' => null,
            'last_contract_end_date' => null,
            'job_progression' => null,
        ]);

        $user->save();

        return $user;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $message = '';

                if (!empty($this->duplicates)) {
                    $message .= "تم تخطي الموظفين المكررين التالية:\n";
                    foreach ($this->duplicates as $duplicate) {
                        $message .= "- {$duplicate['employee_name']} (#{$duplicate['employee_id']}): {$duplicate['reason']}\n";
                    }
                }

                if (!empty($this->skippedRows)) {
                    $message .= "\nتم تخطي السجلات التالية:\n";
                    foreach ($this->skippedRows as $skipped) {
                        $message .= "- {$skipped['employee_name']}: {$skipped['reason']}\n";
                    }
                }

                if (!empty($message)) {
                    Session::flash('import_summary', $message);
                    Session::flash('skipped_count', count($this->duplicates) + count($this->skippedRows));
                }
            }
        ];
    }
}
