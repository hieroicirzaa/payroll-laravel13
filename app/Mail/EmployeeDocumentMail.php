<?php

namespace App\Mail;

use App\Models\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EmployeeDocument $document,
        private readonly string $fileBinary,
    ) {
    }

    public function build(): static
    {
        $this->document->loadMissing(['company', 'employee.user', 'uploader']);

        return $this
            ->subject('Dokumen Karyawan: '.$this->document->title)
            ->view('emails.employee-document')
            ->with(['document' => $this->document])
            ->attachData($this->fileBinary, $this->document->original_name, [
                'mime' => $this->document->mime_type ?: 'application/octet-stream',
            ]);
    }
}
