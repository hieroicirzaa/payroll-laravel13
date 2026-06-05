<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayrollSlipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Payroll $payroll,
        private readonly string $pdfBinary,
        private readonly string $filename,
    ) {
    }

    public function build(): static
    {
        $this->payroll->loadMissing(['company', 'employee.user', 'period']);

        return $this
            ->subject('Slip Gaji '.$this->payroll->employee?->user?->name.' - '.$this->payroll->period?->name)
            ->view('emails.payroll-slip')
            ->with(['payroll' => $this->payroll])
            ->attachData($this->pdfBinary, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
