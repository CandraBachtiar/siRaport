<?php

namespace App\Exports;

use App\Models\Kelas;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SiswaTemplateExport implements Export, WithMultipleSheets
{
    /** @return array<int, object> */
    public function sheets(): array
    {
        return [new SiswaTemplateDataSheet, new SiswaClassReferenceSheet];
    }
}

class SiswaTemplateDataSheet implements FromArray, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @return array<int, array<int, never>> */
    public function array(): array
    {
        return [];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin', 'Kelas', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'A2:A1000' => NumberFormat::FORMAT_TEXT,
            'B2:B1000' => NumberFormat::FORMAT_TEXT,
            'E2:E1000' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function title(): string
    {
        return 'Data Siswa';
    }
}

class SiswaClassReferenceSheet implements FromArray, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @return array<int, array<int, int|string>> */
    public function array(): array
    {
        return Kelas::query()
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->orderBy('id')
            ->get(['id', 'tingkat', 'nama'])
            ->map(fn (Kelas $kelas): array => [$kelas->id, $kelas->tingkat.' '.$kelas->nama])
            ->all();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['ID Kelas (isi kolom Kelas dengan nilai ini)', 'Tingkat dan Nama Kelas'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A2:A1000' => NumberFormat::FORMAT_TEXT];
    }

    public function title(): string
    {
        return 'Daftar Kelas';
    }
}
