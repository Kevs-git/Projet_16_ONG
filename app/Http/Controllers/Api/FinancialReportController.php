<?php

namespace App\Http\Controllers\Api;

use App\Exports\FinancialReportExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialReportController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->only(['from', 'to']);
        $filename = 'financial-report-'.Str::kebab(now()->toDateString()).'.xlsx';

        return Excel::download(new FinancialReportExport($payload), $filename);
    }
}
