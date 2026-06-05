<p>Yth. {{ $payroll->employee->user->name }},</p>

<p>Berikut kami lampirkan slip gaji untuk periode {{ $payroll->period->name }}.</p>

<p>
    Company: {{ $payroll->company->name }}<br>
    Bulan ke: {{ $payroll->period->period_month }}<br>
    Total diterima: Rp. {{ number_format((float) $payroll->net_amount, 0, ',', '.') }}
</p>

<p>Email ini dikirim otomatis oleh sistem payroll.</p>
