@php
    use App\Domain\Enums\SalaryComponentType;

    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $currency = fn ($value) => 'Rp. '.number_format((float) $value, 0, ',', '.');
    $typeValue = fn ($item) => $item->type instanceof SalaryComponentType ? $item->type->value : (string) $item->type;

    $baseItem = $payroll->items->first(fn ($item) => blank($item->salary_component_id));
    $baseAmount = $baseItem ? (float) $baseItem->amount : (float) $payroll->employee->basic_salary;

    $earningItems = $payroll->items->filter(fn ($item) => $typeValue($item) === SalaryComponentType::Earning->value && filled($item->salary_component_id))->values();
    $deductionItems = $payroll->items->filter(fn ($item) => $typeValue($item) === SalaryComponentType::Deduction->value)->values();

    $variableKeywords = ['bonus', 'lembur', 'rapel', 'reimburse', 'insentif'];
    $variableEarnings = $earningItems->filter(function ($item) use ($variableKeywords) {
        $name = strtolower($item->name);
        foreach ($variableKeywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }
        return false;
    })->values();
    $fixedEarnings = $earningItems->reject(fn ($item) => $variableEarnings->contains('id', $item->id))->values();

    $subtotalB = $fixedEarnings->sum(fn ($item) => (float) $item->amount);
    $subtotalC = $variableEarnings->sum(fn ($item) => (float) $item->amount);
    $subtotalD = $deductionItems->sum(fn ($item) => (float) $item->amount) + (float) $payroll->tax_amount;
    $month = (int) $payroll->period->period_month;
    $periodLabel = $payroll->period->period_year.' - '.($monthNames[$month] ?? $month);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #222; font-size: 11px; margin: 0; }
        .page { padding: 14px 18px 12px; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; position: relative; }
        .logo { position: absolute; left: 12px; top: 8px; width: 58px; height: 58px; border-radius: 50%; border: 4px solid #16a34a; text-align: center; line-height: 50px; font-size: 15px; font-weight: bold; color: #0f766e; }
        .company { font-size: 27px; letter-spacing: 1px; font-weight: 500; text-transform: uppercase; }
        .subtitle { font-size: 17px; margin-top: 5px; }
        .period-title { font-size: 20px; margin-top: 4px; font-weight: bold; }
        .info { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .info td { border-bottom: 1px solid #d6d6d6; padding: 3px 4px; vertical-align: top; }
        .label { width: 100px; color: #333; }
        .colon { width: 10px; text-align: center; }
        .gap { width: 28px; border-bottom: 0 !important; }
        .section-wrap { width: 100%; margin-top: 18px; border-top: 2px solid #111; padding-top: 10px; }
        .columns { width: 100%; border-collapse: collapse; }
        .columns > tbody > tr > td { width: 50%; vertical-align: top; }
        .left-col { padding-right: 18px; }
        .right-col { padding-left: 18px; }
        .pay-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .pay-table th { text-align: left; padding: 5px 0; border-bottom: 1px solid #bfbfbf; font-size: 11px; }
        .pay-table td { padding: 3px 0; border-bottom: 1px solid #e0e0e0; }
        .amount { width: 105px; text-align: right; white-space: nowrap; }
        .subtotal td { font-weight: bold; border-top: 1px solid #bfbfbf; padding-top: 7px; }
        .net-box { margin-top: 14px; padding: 9px 10px; border-top: 2px solid #111; border-bottom: 2px solid #111; font-weight: bold; font-size: 13px; }
        .net-box table { width: 100%; border-collapse: collapse; }
        .net-box td:last-child { text-align: right; }
        .verification { margin-top: 24px; width: 112px; height: 112px; border: 8px solid #111; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 10px; font-weight: bold; line-height: 1.2; }
        .signature { width: 100%; margin-top: 42px; border-top: 2px solid #111; padding-top: 28px; border-collapse: collapse; }
        .signature td { width: 33.33%; text-align: center; vertical-align: top; height: 95px; }
        .note { text-align: left !important; line-height: 1.4; padding-left: 20px; }
        .footer { position: fixed; bottom: 12px; left: 18px; right: 18px; font-size: 8px; line-height: 1.35; color: #333; border-top: 1px solid #eee; padding-top: 5px; }
        .muted { color: #666; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="logo">PAY</div>
        <div class="company">{{ $payroll->company->name }}</div>
        <div class="subtitle">{{ $payroll->company->code ?? 'PAYROLL SYSTEM' }}</div>
        <div class="period-title">SLIP GAJI</div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Nama</td><td class="colon">:</td><td>{{ $payroll->employee->user->name }}</td>
            <td class="gap"></td>
            <td class="label">Periode</td><td class="colon">:</td><td>{{ $periodLabel }}</td>
        </tr>
        <tr>
            <td class="label">NIK</td><td class="colon">:</td><td>{{ $payroll->employee->employee_number }}</td>
            <td class="gap"></td>
            <td class="label">Bulan Ke</td><td class="colon">:</td><td>{{ $month }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td><td class="colon">:</td><td>{{ $payroll->employee->position }}</td>
            <td class="gap"></td>
            <td class="label">Direktorat</td><td class="colon">:</td><td>{{ $payroll->employee->department ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Regional</td><td class="colon">:</td><td>{{ $payroll->company->code ?: '-' }}</td>
            <td class="gap"></td>
            <td class="label">Bank</td><td class="colon">:</td><td>{{ $payroll->employee->bank_name ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Lokasi Kerja</td><td class="colon">:</td><td>{{ $payroll->company->address ?: '-' }}</td>
            <td class="gap"></td>
            <td class="label">No. Rek.</td><td class="colon">:</td><td>{{ $payroll->employee->bank_account_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td><td class="colon">:</td><td>{{ strtoupper($payroll->employee->employment_status) }}</td>
            <td class="gap"></td>
            <td class="label">An.</td><td class="colon">:</td><td>{{ $payroll->employee->bank_account_name ?: $payroll->employee->user->name }}</td>
        </tr>
    </table>

    <div class="section-wrap">
        <table class="columns">
            <tr>
                <td class="left-col">
                    <table class="pay-table">
                        <tr><th colspan="2">A. BASE SALARY</th></tr>
                        <tr><td>Gaji Pokok</td><td class="amount">{{ $currency($baseAmount) }}</td></tr>
                    </table>

                    <table class="pay-table">
                        <tr><th colspan="2">B. TUNJANGAN TETAP / PENDAPATAN</th></tr>
                        @forelse ($fixedEarnings as $index => $item)
                            <tr><td>{{ $index + 1 }}. {{ $item->name }}</td><td class="amount">{{ $currency($item->amount) }}</td></tr>
                        @empty
                            <tr><td>1. -</td><td class="amount">{{ $currency(0) }}</td></tr>
                        @endforelse
                        <tr class="subtotal"><td>SUB TOTAL B</td><td class="amount">{{ $currency($subtotalB) }}</td></tr>
                    </table>

                    <table class="pay-table">
                        <tr><th colspan="2">C. TUNJANGAN TIDAK TETAP</th></tr>
                        @forelse ($variableEarnings as $index => $item)
                            <tr><td>{{ $index + 1 }}. {{ $item->name }}</td><td class="amount">{{ $currency($item->amount) }}</td></tr>
                        @empty
                            <tr><td>1. -</td><td class="amount">{{ $currency(0) }}</td></tr>
                        @endforelse
                        <tr class="subtotal"><td>SUB TOTAL C</td><td class="amount">{{ $currency($subtotalC) }}</td></tr>
                    </table>
                </td>
                <td class="right-col">
                    <table class="pay-table">
                        <tr><th colspan="2">D. POTONGAN</th></tr>
                        <tr><td>1. Pot. Pph 21</td><td class="amount">{{ $currency($payroll->tax_amount) }}</td></tr>
                        @forelse ($deductionItems as $index => $item)
                            <tr><td>{{ $index + 2 }}. {{ $item->name }}</td><td class="amount">{{ $currency($item->amount) }}</td></tr>
                        @empty
                            <tr><td>2. -</td><td class="amount">{{ $currency(0) }}</td></tr>
                        @endforelse
                        <tr class="subtotal"><td>SUB TOTAL D</td><td class="amount">{{ $currency($subtotalD) }}</td></tr>
                    </table>

                    <div class="net-box">
                        <table>
                            <tr>
                                <td>TOTAL PENDAPATAN {{ '{(A+B+C)-D}' }}</td>
                                <td>{{ $currency($payroll->net_amount) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="verification">
                        SLIP<br>PAYROLL<br>#{{ $payroll->id }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="signature">
        <tr>
            <td>
                Di Buat Oleh,<br><br><br><br>
                {{ $payroll->company->name }}
            </td>
            <td>
                Diterima Oleh,<br><br><br><br>
                {{ $payroll->employee->user->name }}
            </td>
            <td class="note">
                <strong>Note:</strong><br>
                Jika terdapat kekurangan / kelebihan, akan diperhitungkan pada bulan berikutnya.
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    This is computer generated statement issued by {{ $payroll->company->name }} and requires no signature.<br>
    Laporan ini dihasilkan oleh komputer dan tidak memerlukan tanda tangan. Perusahaan tidak bertanggung jawab atas penggunaan laporan ini setelah diterima oleh yang bersangkutan.
</div>
</body>
</html>
