<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GuruTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\GuruImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuruImportController extends Controller
{
    public function __construct(private readonly GuruImportService $importService) {}

    public function create(Request $request): View
    {
        return view('admin.guru.import', [
            'admin' => $request->user(),
            'previewRows' => session('guru_import_preview', []),
            'headerError' => session('guru_import_header_error'),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new GuruTemplateExport, 'template-import-guru.xlsx');
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.file' => 'File yang dikirim tidak valid.',
            'file.mimes' => 'File tidak valid. Silakan gunakan file Excel (.xlsx) sesuai template.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $result = $this->importService->preview($request->file('file'));

        if ($result['headerError'] !== null) {
            return redirect()->route('admin.guru.import.create')
                ->with('guru_import_header_error', $result['headerError']);
        }

        return redirect()->route('admin.guru.import.create')
            ->with('guru_import_preview', $result['rows']);
    }

    public function import(Request $request): RedirectResponse
    {
        $rows = session('guru_import_preview', []);

        if ($rows === []) {
            return redirect()->route('admin.guru.import.create')->with('error', 'Belum ada data valid untuk diimport.');
        }

        if (collect($rows)->contains(fn (array $row): bool => ! $row['valid'])) {
            return redirect()->route('admin.guru.import.create')->with('error', 'Perbaiki semua data yang error sebelum melakukan import.');
        }

        $count = $this->importService->import($rows);
        $request->session()->forget('guru_import_preview');

        return redirect()->route('admin.guru.index')->with('success', $count.' data guru berhasil diimport.');
    }
}
