<?php

namespace App\Application\Employees\Imports;

use App\Models\Company;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportTemplate
{
    /**
     * Header ini sekaligus menjadi kontrak format impor.
     * Jangan mengubah urutan tanpa menyesuaikan EmployeeBulkImportService.
     */
    public const HEADERS = [
        'company_code',
        'name',
        'email',
        'password',
        'employee_number',
        'position',
        'department',
        'join_date',
        'employment_status',
        'basic_salary',
        'nik',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'phone',
        'address',
        'status',
    ];

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('employee_import');

        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray($this->exampleRows(), null, 'A2');

        $highestColumn = $sheet->getHighestColumn();
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$highestColumn}1");

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);

        $sheet->getStyle("A2:{$highestColumn}4")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
        ]);

        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $dateValidation = $sheet->getCell('H2')->getDataValidation();
        $dateValidation->setType(DataValidation::TYPE_DATE);
        $dateValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $dateValidation->setAllowBlank(false);
        $dateValidation->setShowInputMessage(true);
        $dateValidation->setPromptTitle('Format tanggal');
        $dateValidation->setPrompt('Gunakan format YYYY-MM-DD, contoh 2026-01-15.');

        $statusValidation = $sheet->getCell('R2')->getDataValidation();
        $statusValidation->setType(DataValidation::TYPE_LIST);
        $statusValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $statusValidation->setAllowBlank(false);
        $statusValidation->setShowDropDown(true);
        $statusValidation->setFormula1('"active,inactive"');

        $employmentValidation = $sheet->getCell('I2')->getDataValidation();
        $employmentValidation->setType(DataValidation::TYPE_LIST);
        $employmentValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $employmentValidation->setAllowBlank(false);
        $employmentValidation->setShowDropDown(true);
        $employmentValidation->setFormula1('"permanent,contract,probation,intern"');

        for ($row = 2; $row <= 500; $row++) {
            $sheet->getCell("H{$row}")->setDataValidation(clone $dateValidation);
            $sheet->getCell("I{$row}")->setDataValidation(clone $employmentValidation);
            $sheet->getCell("R{$row}")->setDataValidation(clone $statusValidation);
        }

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('panduan');
        $guide->fromArray([
            ['Panduan impor karyawan'],
            ['1. Jangan mengubah nama header pada baris pertama. Sistem membaca dan mencocokkan header berdasarkan kata kunci.'],
            ['2. company_code wajib untuk Super Admin. Admin Company boleh mengosongkan company_code karena sistem memakai company akun login.'],
            ['3. Kolom wajib: name, email, password, employee_number, position, join_date, employment_status, basic_salary, status.'],
            ['4. Format join_date: YYYY-MM-DD. Contoh: 2026-01-15.'],
            ['5. basic_salary harus angka murni. Contoh: 7000000. Jangan gunakan simbol Rp.'],
            ['6. status hanya boleh active atau inactive.'],
            ['7. Satu file boleh memuat banyak baris. Sistem membaca file per chunk agar lebih aman untuk data besar.'],
            ['8. Jika header wajib tidak ditemukan, file ditolak sebelum proses impor. Jika baris tertentu salah, baris itu ditolak dan dilaporkan.'],
            ['Kode company aktif yang tersedia'],
        ], null, 'A1');

        $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $guide->getColumnDimension('A')->setWidth(120);
        $guide->getStyle('A1:A9')->getAlignment()->setWrapText(true);

        $companies = Company::query()->where('is_active', true)->orderBy('name')->get(['code', 'name']);
        $guide->fromArray(['company_code', 'company_name'], null, 'A11');
        $row = 12;
        foreach ($companies as $company) {
            $guide->setCellValue("A{$row}", $company->code);
            $guide->setCellValue("B{$row}", $company->name);
            $row++;
        }
        $guide->getStyle('A11:B11')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
        ]);
        $guide->getColumnDimension('B')->setWidth(42);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer, $spreadsheet): void {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template_import_karyawan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function exampleRows(): array
    {
        $companyCode = Company::query()->where('is_active', true)->orderBy('id')->value('code') ?: 'ALPHA';

        return [
            [$companyCode, 'Budi Santoso', 'budi.santoso@example.com', 'Password123!', 'EMP-1001', 'Staff Finance', 'Finance', '2026-01-15', 'permanent', 7000000, '3276000000000001', '09.123.456.7-001.000', 'BCA', '1234567890', 'Budi Santoso', '081234567890', 'Jakarta', 'active'],
            [$companyCode, 'Siti Aminah', 'siti.aminah@example.com', 'Password123!', 'EMP-1002', 'HR Officer', 'Human Resource', '2026-02-01', 'contract', 6500000, '', '', 'Mandiri', '9876543210', 'Siti Aminah', '081298765432', 'Bandung', 'active'],
        ];
    }
}
