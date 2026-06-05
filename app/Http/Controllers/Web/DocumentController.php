<?php

namespace App\Http\Controllers\Web;

use App\Application\Employees\CompanyScope;
use App\Domain\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Mail\EmployeeDocumentMail;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly CompanyScope $scope)
    {
    }

    public function index(): Response
    {
        $actor = request()->user();

        return Inertia::render('Documents/Index', [
            'documents' => EmployeeDocument::query()
                ->with(['employee.user', 'company', 'uploader'])
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->when($actor->isEmployee(), fn ($q) => $q->where('employee_id', $actor->employee?->id ?? 0))
                ->latest()
                ->get(),
            'employees' => Employee::query()
                ->with('user')
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->when($actor->isEmployee(), fn ($q) => $q->whereKey($actor->employee?->id ?? 0))
                ->where('status', 'active')
                ->orderBy('employee_number')
                ->get(),
            'types' => collect(DocumentType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => strtoupper($type->value)])->values(),
        ]);
    }

    public function show(Request $request, EmployeeDocument $document): Response
    {
        $this->scope->assertEmployeeAccessible($request->user(), $document->employee);

        return Inertia::render('Documents/Show', [
            'document' => $document->load(['employee.user', 'company', 'uploader']),
            'canPreview' => $this->canPreviewInline($document),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(array_map(fn ($type) => $type->value, DocumentType::cases()))],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:5120'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->scope->assertEmployeeAccessible($request->user(), $employee);

        $file = $request->file('file');
        $path = $file->store("employees/{$employee->id}", 'payroll_private');

        EmployeeDocument::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'disk' => 'payroll_private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function preview(Request $request, EmployeeDocument $document): HttpResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $document->employee);
        abort_unless($this->canPreviewInline($document), 415, 'File ini tidak dapat dipratinjau di browser.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return response(Storage::disk($document->disk)->get($document->path), 200, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function download(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $document->employee);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function email(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $document->employee);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $document->loadMissing(['employee.user', 'company', 'uploader']);
        $email = $document->employee?->user?->email;
        abort_if(blank($email), 422, 'Email karyawan tidak tersedia.');

        Mail::to($email)->send(new EmployeeDocumentMail($document, Storage::disk($document->disk)->get($document->path)));

        return back()->with('success', 'Dokumen berhasil dikirim ke email karyawan.');
    }

    public function destroy(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $document->employee);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    private function canPreviewInline(EmployeeDocument $document): bool
    {
        return str($document->mime_type)->startsWith(['application/pdf', 'image/']);
    }
}
