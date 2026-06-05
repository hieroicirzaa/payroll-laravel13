<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SelfServiceController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('company', 'employee'),
        ]);
    }

    public function payrolls(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        return response()->json([
            'data' => $employee->payrolls()->with('period', 'items')->latest()->paginate(12),
        ]);
    }

    public function documents(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        return response()->json([
            'data' => $employee->documents()->latest()->get(),
        ]);
    }
}
