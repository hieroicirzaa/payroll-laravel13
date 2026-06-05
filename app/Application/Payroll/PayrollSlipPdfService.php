<?php

namespace App\Application\Payroll;

use App\Models\Payroll;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

class PayrollSlipPdfService
{
    public function output(Payroll $payroll): string
    {
        $payroll->loadMissing(['company', 'employee.user', 'period', 'items']);

        $html = view('payroll.slip-pdf', [
            'payroll' => $payroll,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.user', 'period']);

        $name = Str::slug($payroll->employee?->user?->name ?: 'karyawan', '_');
        $year = $payroll->period?->period_year ?: now()->year;
        $month = $payroll->period?->period_month ?: now()->month;

        return "slip-gaji-{$name}-{$year}-{$month}-{$payroll->id}.pdf";
    }
}
