<?php

namespace App\Application\Employees\Exports;

use App\Models\Employee;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeExportService
{
    public function download(User $actor): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan');

        $headers = [
            'No',
            'Company Code',
            'Company Name',
            'Employee Number',
            'Name',
            'Email',
            'Position',
            'Department',
            'Join Date',
            'Employment Status',
            'Basic Salary',
            'NIK',
            'NPWP',
            'Bank Name',
            'Bank Account Number',
            'Bank Account Name',
            'Phone',
            'Address',
            'Status',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:S1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2E8F0'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        $number = 1;

        Employee::query()
            ->with(['company', 'user'])
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('company_id', $actor->company_id))
            ->orderBy('company_id')
            ->orderBy('employee_number')
            ->chunkById(500, function ($employees) use (&$row, &$number, $sheet): void {
                foreach ($employees as $employee) {
                    $sheet->setCellValue("A{$row}", $number++);
                    $sheet->setCellValue("B{$row}", $employee->company?->code);
                    $sheet->setCellValue("C{$row}", $employee->company?->name);
                    $sheet->setCellValueExplicit("D{$row}", (string) $employee->employee_number, DataType::TYPE_STRING);
                    $sheet->setCellValue("E{$row}", $employee->user?->name);
                    $sheet->setCellValue("F{$row}", $employee->user?->email);
                    $sheet->setCellValue("G{$row}", $employee->position);
                    $sheet->setCellValue("H{$row}", $employee->department);
                    $sheet->setCellValue("I{$row}", optional($employee->join_date)->format('Y-m-d'));
                    $sheet->setCellValue("J{$row}", $employee->employment_status);
                    $sheet->setCellValue("K{$row}", (float) $employee->basic_salary);
                    $sheet->setCellValueExplicit("L{$row}", (string) ($employee->nik ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("M{$row}", (string) ($employee->npwp ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValue("N{$row}", $employee->bank_name);
                    $sheet->setCellValueExplicit("O{$row}", (string) ($employee->bank_account_number ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValue("P{$row}", $employee->bank_account_name);
                    $sheet->setCellValueExplicit("Q{$row}", (string) ($employee->phone ?? ''), DataType::TYPE_STRING);
                    $sheet->setCellValue("R{$row}", $employee->address);
                    $sheet->setCellValue("S{$row}", $employee->status);
                    $row++;
                }
            });

        foreach (range('A', 'S') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle("A1:S".max(1, $row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('K2:K'.max(2, $row - 1))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:S1');

        $filename = 'export-karyawan-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
