<?php

namespace App\Application\Employees\Imports;

use App\Application\Employees\CompanyScope;
use App\Domain\Enums\UserRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeBulkImportService
{
    private const CHUNK_SIZE = 500;
    private const MAX_FAILURES_IN_SESSION = 100;

    /** @var array<string, array<int, string>> */
    private const HEADER_ALIASES = [
        'company_code' => ['company_code', 'kode_company', 'kode_perusahaan', 'company', 'perusahaan'],
        'name' => ['name', 'nama', 'nama_karyawan', 'employee_name'],
        'email' => ['email', 'alamat_email', 'email_karyawan'],
        'password' => ['password', 'kata_sandi', 'sandi'],
        'employee_number' => ['employee_number', 'nomor_karyawan', 'no_karyawan', 'nik_karyawan', 'id_karyawan'],
        'position' => ['position', 'jabatan', 'posisi'],
        'department' => ['department', 'departemen', 'divisi'],
        'join_date' => ['join_date', 'tanggal_masuk', 'tgl_masuk'],
        'employment_status' => ['employment_status', 'status_kepegawaian', 'jenis_karyawan'],
        'basic_salary' => ['basic_salary', 'gaji_pokok', 'gaji'],
        'nik' => ['nik', 'nomor_ktp', 'ktp'],
        'npwp' => ['npwp'],
        'bank_name' => ['bank_name', 'nama_bank', 'bank'],
        'bank_account_number' => ['bank_account_number', 'nomor_rekening', 'no_rekening', 'rekening'],
        'bank_account_name' => ['bank_account_name', 'nama_rekening', 'atas_nama_rekening'],
        'phone' => ['phone', 'telepon', 'no_hp', 'hp'],
        'address' => ['address', 'alamat'],
        'status' => ['status', 'status_akun'],
    ];

    private const REQUIRED_HEADERS = [
        'name',
        'email',
        'password',
        'employee_number',
        'position',
        'join_date',
        'employment_status',
        'basic_salary',
        'status',
    ];

    public function __construct(private readonly CompanyScope $scope)
    {
    }

    /**
     * @return array{inserted:int, failed:int, processed:int, failures:array<int, array<string, mixed>>, header_map:array<string, int>}
     */
    public function import(UploadedFile $file, User $actor): array
    {
        $path = $file->getRealPath();
        if (! $path) {
            $this->rejectFile('File impor tidak dapat dibaca.');
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $worksheetInfo = $reader->listWorksheetInfo($path);
        $firstWorksheet = $worksheetInfo[0] ?? null;
        $highestRow = (int) ($firstWorksheet['totalRows'] ?? 0);

        if ($highestRow < 2) {
            $this->rejectFile('File tidak memiliki data karyawan. Minimal satu baris data wajib diisi.');
        }

        $headerSpreadsheet = $this->loadRows($reader, $path, 1, 1);
        $headerSheet = $headerSpreadsheet->getActiveSheet();
        $headerMap = $this->resolveHeaderMap($headerSheet);
        $headerSpreadsheet->disconnectWorksheets();

        $this->assertRequiredHeaders($headerMap, $actor);

        $inserted = 0;
        $failed = 0;
        $processed = 0;
        $failures = [];

        for ($startRow = 2; $startRow <= $highestRow; $startRow += self::CHUNK_SIZE) {
            $endRow = min($startRow + self::CHUNK_SIZE - 1, $highestRow);
            $spreadsheet = $this->loadRows($reader, $path, $startRow, $endRow);
            $sheet = $spreadsheet->getActiveSheet();

            for ($row = $startRow; $row <= $endRow; $row++) {
                if ($this->isBlankRow($sheet, $row, $headerMap)) {
                    continue;
                }

                $processed++;
                $payload = $this->extractPayload($sheet, $row, $headerMap, $actor);
                $validator = Validator::make($payload, $this->rules($actor, $payload));

                if ($validator->fails()) {
                    $failed++;
                    $this->pushFailure($failures, $row, $payload, $validator->errors()->all());
                    continue;
                }

                try {
                    DB::transaction(function () use ($payload): void {
                        $user = User::create([
                            'company_id' => $payload['company_id'],
                            'name' => $payload['name'],
                            'email' => $payload['email'],
                            'password' => Hash::make($payload['password']),
                            'role' => UserRole::Employee,
                            'is_active' => $payload['status'] === 'active',
                        ]);

                        Employee::create([
                            'company_id' => $payload['company_id'],
                            'user_id' => $user->id,
                            'employee_number' => $payload['employee_number'],
                            'position' => $payload['position'],
                            'department' => $payload['department'] ?: null,
                            'join_date' => $payload['join_date'],
                            'employment_status' => $payload['employment_status'],
                            'basic_salary' => $payload['basic_salary'],
                            'nik' => $payload['nik'] ?: null,
                            'npwp' => $payload['npwp'] ?: null,
                            'bank_name' => $payload['bank_name'] ?: null,
                            'bank_account_number' => $payload['bank_account_number'] ?: null,
                            'bank_account_name' => $payload['bank_account_name'] ?: null,
                            'address' => $payload['address'] ?: null,
                            'phone' => $payload['phone'] ?: null,
                            'status' => $payload['status'],
                        ]);
                    });

                    $inserted++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->pushFailure($failures, $row, $payload, ['Gagal menyimpan data. Periksa duplikasi email/nomor karyawan atau validasi database.']);
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
        }

        return [
            'inserted' => $inserted,
            'failed' => $failed,
            'processed' => $processed,
            'failures' => $failures,
            'header_map' => $headerMap,
        ];
    }

    private function loadRows(IReader $reader, string $path, int $startRow, int $endRow): Spreadsheet
    {
        $reader->setReadFilter(new EmployeeImportChunkReadFilter($startRow, $endRow));

        return $reader->load($path);
    }

    /** @return array<string, int> */
    private function resolveHeaderMap(Worksheet $sheet): array
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $map = [];

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $header = $this->normalizeHeader((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue());
            if ($header === '') {
                continue;
            }

            foreach (self::HEADER_ALIASES as $canonical => $aliases) {
                if (in_array($header, $aliases, true)) {
                    $map[$canonical] ??= $column;
                    break;
                }
            }
        }

        return $map;
    }

    /** @param array<string, int> $headerMap */
    private function assertRequiredHeaders(array $headerMap, User $actor): void
    {
        $required = self::REQUIRED_HEADERS;
        if ($actor->isSuperAdmin()) {
            $required[] = 'company_code';
        }

        $missing = array_values(array_diff($required, array_keys($headerMap)));
        if ($missing !== []) {
            $this->rejectFile('Format Excel ditolak. Header wajib tidak ditemukan: '.implode(', ', $missing).'. Download template terbaru dari web.');
        }
    }

    /** @throws ValidationException */
    private function rejectFile(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }

    private function normalizeHeader(string $value): string
    {
        return str($value)
            ->lower()
            ->trim()
            ->replace([' ', '-', '.', '/', '\\'], '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    /** @param array<string, int> $headerMap */
    private function isBlankRow(Worksheet $sheet, int $row, array $headerMap): bool
    {
        foreach ($headerMap as $column) {
            $value = trim((string) $this->cellValue($sheet, $column, $row));
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, int> $headerMap */
    private function extractPayload(Worksheet $sheet, int $row, array $headerMap, User $actor): array
    {
        $companyId = null;
        $companyCode = $this->valueByHeader($sheet, $row, $headerMap, 'company_code');

        if ($actor->isSuperAdmin()) {
            $company = Company::query()
                ->where('code', $companyCode)
                ->where('is_active', true)
                ->first();
            $companyId = $company?->id;
        } else {
            $companyId = $this->scope->targetCompanyId($actor);
        }

        return [
            'company_id' => $companyId,
            'company_code' => $companyCode,
            'name' => $this->valueByHeader($sheet, $row, $headerMap, 'name'),
            'email' => strtolower($this->valueByHeader($sheet, $row, $headerMap, 'email')),
            'password' => $this->valueByHeader($sheet, $row, $headerMap, 'password'),
            'employee_number' => $this->valueByHeader($sheet, $row, $headerMap, 'employee_number'),
            'position' => $this->valueByHeader($sheet, $row, $headerMap, 'position'),
            'department' => $this->valueByHeader($sheet, $row, $headerMap, 'department'),
            'join_date' => $this->normalizeDate($this->cellRawValueByHeader($sheet, $row, $headerMap, 'join_date'), $sheet, $row, $headerMap['join_date'] ?? null),
            'employment_status' => strtolower($this->valueByHeader($sheet, $row, $headerMap, 'employment_status')),
            'basic_salary' => $this->normalizeMoney($this->cellRawValueByHeader($sheet, $row, $headerMap, 'basic_salary')),
            'nik' => $this->valueByHeader($sheet, $row, $headerMap, 'nik'),
            'npwp' => $this->valueByHeader($sheet, $row, $headerMap, 'npwp'),
            'bank_name' => $this->valueByHeader($sheet, $row, $headerMap, 'bank_name'),
            'bank_account_number' => $this->valueByHeader($sheet, $row, $headerMap, 'bank_account_number'),
            'bank_account_name' => $this->valueByHeader($sheet, $row, $headerMap, 'bank_account_name'),
            'phone' => $this->valueByHeader($sheet, $row, $headerMap, 'phone'),
            'address' => $this->valueByHeader($sheet, $row, $headerMap, 'address'),
            'status' => strtolower($this->valueByHeader($sheet, $row, $headerMap, 'status') ?: 'active'),
        ];
    }

    private function rules(User $actor, array $payload): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'company_code' => [$actor->isSuperAdmin() ? 'required' : 'nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()],
            'employee_number' => ['required', 'string', 'max:80', Rule::unique('employees', 'employee_number')],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', 'string', 'max:80'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'nik' => ['nullable', 'string', 'max:80'],
            'npwp' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /** @param array<string, int> $headerMap */
    private function valueByHeader(Worksheet $sheet, int $row, array $headerMap, string $header): string
    {
        if (! isset($headerMap[$header])) {
            return '';
        }

        return trim((string) $this->cellValue($sheet, $headerMap[$header], $row));
    }

    /** @param array<string, int> $headerMap */
    private function cellRawValueByHeader(Worksheet $sheet, int $row, array $headerMap, string $header): mixed
    {
        if (! isset($headerMap[$header])) {
            return null;
        }

        return $sheet->getCell(Coordinate::stringFromColumnIndex($headerMap[$header]).$row)->getValue();
    }

    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
        $value = $cell->getValue();

        if (ExcelDate::isDateTime($cell) && is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return $cell->getFormattedValue();
    }

    private function normalizeDate(mixed $value, Worksheet $sheet, int $row, ?int $column): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($column !== null) {
            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
            if (ExcelDate::isDateTime($cell) && is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function normalizeMoney(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = str((string) $value)
            ->replace(['Rp', 'rp', 'IDR', 'idr', ' '], '')
            ->toString();

        if (preg_match('/^-?\d{1,3}([.,]\d{3})+$/', $text)) {
            $text = str_replace([',', '.'], '', $text);
        } elseif (str_contains($text, ',') && str_contains($text, '.')) {
            $text = str_replace(',', '', $text);
        } elseif (str_contains($text, ',') && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        } elseif (substr_count($text, '.') > 1) {
            $text = str_replace('.', '', $text);
        }

        $text = preg_replace('/[^0-9.\-]/', '', $text);

        return is_numeric($text) ? (float) $text : $value;
    }

    /** @param array<int, array<string, mixed>> $failures */
    private function pushFailure(array &$failures, int $row, array $payload, array $errors): void
    {
        if (count($failures) >= self::MAX_FAILURES_IN_SESSION) {
            return;
        }

        $failures[] = [
            'row' => $row,
            'employee_number' => Arr::get($payload, 'employee_number'),
            'email' => Arr::get($payload, 'email'),
            'errors' => $errors,
        ];
    }
}
