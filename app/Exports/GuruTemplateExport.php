<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GuruTemplateExport implements FromArray, WithColumnFormatting, WithHeadings
{
    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['NIP', 'Nama Guru', 'Email'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT];
    }
}
