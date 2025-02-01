<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ExcelProcessingService
{
    protected $emailService;

    public function __construct(SalaryEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function processExcelFile(UploadedFile $file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // تخطي الصف الأول (العناوين)
            array_shift($rows);

            $results = [
                'success' => [],
                'failed' => []
            ];

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // تخطي الصفوف الفارغة

                try {
                    $employeeId = trim($row[0]);
                    $employee = User::where('employee_id', $employeeId)->first();

                    if (!$employee) {
                        throw new \Exception("Employee not found: {$employeeId}");
                    }

                    // تحويل صف البيانات إلى HTML
                    $htmlContent = $this->convertRowToHtml($row);

                    // إرسال البريد الإلكتروني
                    $this->emailService->sendFormattedEmail(
                        $employee->email,
                        $htmlContent,
                        "Your Salary Details"
                    );

                    $results['success'][] = [
                        'employee_id' => $employeeId,
                        'email' => $employee->email
                    ];

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'employee_id' => $employeeId ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::error("Failed to process row for employee", [
                        'employee_id' => $employeeId ?? 'Unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Failed to process Excel file", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function convertRowToHtml(array $row): string
    {
        $html = '<table style="border-collapse: collapse; width: 100%; max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">';
        $html .= '<thead style="background-color: #f8f9fa;">';
        $html .= '<tr>';
        $headers = ['Employee ID', 'Basic Salary', 'Allowances', 'Deductions', 'Net Salary']; // قم بتعديل العناوين حسب أعمدة ملف Excel الخاص بك
        foreach ($headers as $header) {
            $html .= "<th style='padding: 12px; border: 1px solid #dee2e6; text-align: left;'>{$header}</th>";
        }
        $html .= '</tr></thead><tbody>';

        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= "<td style='padding: 12px; border: 1px solid #dee2e6;'>{$cell}</td>";
        }
        $html .= '</tr>';

        $html .= '</tbody></table>';

        return $html;
    }
}
