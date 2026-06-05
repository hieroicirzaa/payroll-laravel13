<p>Yth. {{ $document->employee->user->name }},</p>

<p>Berikut kami lampirkan dokumen karyawan yang tersimpan pada sistem payroll.</p>

<p>
    Judul dokumen: {{ $document->title }}<br>
    Jenis dokumen: {{ strtoupper($document->type->value ?? $document->type) }}<br>
    Company: {{ $document->company->name }}<br>
    Tanggal unggah: {{ optional($document->created_at)->format('d-m-Y H:i') }}
</p>

<p>Email ini dikirim otomatis oleh sistem payroll.</p>
