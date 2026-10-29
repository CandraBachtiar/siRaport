<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SiswaTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\SiswaImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiswaImportController extends Controller
{
    public function __construct(private readonly SiswaImportService $importService) {}

    public function create(Request $request): View
    {
        $isPreviewOwner = $request->session()->get('siswa_import_preview_admin_id') === $request->user()->getAuthIdentifier();

        if (! $isPreviewOwner) {
            $request->session()->forget(['siswa_import_preview', 'siswa_import_preview_admin_id']);
        }

        return view('admin.siswa.import', [
            'admin' => $request->user(),
            'previewRows' => $isPreviewOwner ? $request->session()->get('siswa_import_preview', []) : [],
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new SiswaTemplateExport, 'template-import-siswa.xlsx');
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->session()->forget(['siswa_import_preview', 'siswa_import_preview_admin_id']);

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
            return redirect()->route('admin.siswa.import.create')
                ->with('siswa_import_header_error', $result['headerError']);
        }

        $request->session()->put('siswa_import_preview', $result['rows']);
        $request->session()->put('siswa_import_preview_admin_id', $request->user()->getAuthIdentifier());

        return redirect()->route('admin.siswa.import.create');
    }

    public function import(Request $request): RedirectResponse
    {
        $rows = $request->session()->get('siswa_import_preview', []);
        $previewOwnerId = $request->session()->get('siswa_import_preview_admin_id');

        if ($previewOwnerId !== $request->user()->getAuthIdentifier()) {
            $request->session()->forget(['siswa_import_preview', 'siswa_import_preview_admin_id']);

            return redirect()->route('admin.siswa.import.create')
                ->with('error', 'Preview tidak tersedia. Periksa kembali file yang akan diimpor.');
        }

        if ($rows === []) {
            return redirect()->route('admin.siswa.import.create')
                ->with('error', 'Belum ada data valid untuk diimport.');
        }

        if (collect($rows)->contains(fn (array $row): bool => ! ($row['valid'] ?? false))) {
            return redirect()->route('admin.siswa.import.create')
                ->with('error', 'Perbaiki semua data yang error sebelum melakukan import.');
        }

        try {
            $count = $this->importService->import($rows);
        } catch (ValidationException $exception) {
            return redirect()->route('admin.siswa.import.create')->withErrors($exception->errors());
        } catch (QueryException $exception) {
            if (! in_array($exception->errorInfo[0] ?? $exception->getCode(), ['23000', '19'], true)) {
                throw $exception;
            }

            return redirect()->route('admin.siswa.import.create')
                ->with('error', 'Data berubah sejak preview. Periksa ulang file dan lakukan preview kembali.');
        }

        $request->session()->forget(['siswa_import_preview', 'siswa_import_preview_admin_id']);

        return redirect()->route('admin.siswa.index')
            ->with('success', $count.' data siswa berhasil diimport.');
    }
}
