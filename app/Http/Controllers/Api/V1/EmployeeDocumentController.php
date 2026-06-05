<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Application\Employees\CompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request, Employee $employee, CompanyScope $scope): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);

        return response()->json([
            'data' => $employee->documents()->latest()->get(),
        ]);
    }

    public function store(StoreEmployeeDocumentRequest $request, Employee $employee, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);

        $file = $request->file('file');
        $disk = config('filesystems.default') === 'public' ? 'payroll_private' : env('PRIVATE_PAYROLL_DISK', 'payroll_private');
        $path = $file->store("companies/{$employee->company_id}/employees/{$employee->id}", $disk);

        $document = EmployeeDocument::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'uploaded_by' => $request->user()->id,
            'type' => $request->string('type'),
            'title' => $request->string('title'),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
        ]);

        $auditLogger->log($request, 'employee.document.uploaded', $document);

        return response()->json([
            'message' => 'Dokumen berhasil diunggah.',
            'data' => $document,
        ], 201);
    }

    public function download(Request $request, EmployeeDocument $document, CompanyScope $scope): BinaryFileResponse
    {
        $document->load('employee');
        $scope->assertEmployeeAccessible($request->user(), $document->employee);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404, 'File tidak ditemukan.');

        return response()->download(
            Storage::disk($document->disk)->path($document->path),
            $document->original_name
        );
    }

    public function destroy(Request $request, EmployeeDocument $document, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $document->load('employee');
        $scope->assertEmployeeAccessible($request->user(), $document->employee);

        Storage::disk($document->disk)->delete($document->path);
        $auditLogger->log($request, 'employee.document.deleted', $document);
        $document->delete();

        return response()->json(['message' => 'Dokumen berhasil dihapus.']);
    }
}
