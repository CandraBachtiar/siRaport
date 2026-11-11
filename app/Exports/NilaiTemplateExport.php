<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NilaiTemplateExport implements Export, WithMultipleSheets
{
    /**
     * @param  list<array<int, int|string|null>>  $students
     * @param  array<int, string|float|int|null>  $details
     */
    public function __construct(private array $students, private array $details) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        return [new NilaiTemplateDataSheet($this->students), new NilaiTemplateInstructionsSheet($this->details)];
    }
}

class NilaiTemplateDataSheet implements FromArray, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @param list<array<int, int|string|null>> $students */
    public function __construct(private array $students) {}

    /** @return list<array<int, int|string|null>> */
    public function array(): array
    {
        return $this->students;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['ID Penilaian', 'ID Siswa', 'NIS', 'Nama Siswa', 'Nilai'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'A2:A1000' => NumberFormat::FORMAT_TEXT,
            'B2:C1000' => NumberFormat::FORMAT_TEXT,
            'E2:E1000' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function title(): string
    {
        return 'Nilai';
    }
}

class NilaiTemplateInstructionsSheet implements FromArray, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @param array<int, string|float|int|null> $details */
    public function __construct(private array $details) {}

    /** @return array<int, array<int, string|float|int|null>> */
    public function array(): array
    {
        return [
            ['Penilaian', $this->details['assessment_name'] ?? ''],
            ['ID Penilaian', $this->details['assessment_id'] ?? ''],
            ['Mata Pelajaran', $this->details['subject'] ?? ''],
            ['Kelas', $this->details['class'] ?? ''],
            ['Tujuan Pembelajaran', $this->details['goal'] ?? ''],
            ['Petunjuk', 'Isi hanya kolom Nilai. Jangan mengubah ID Penilaian atau ID Siswa.'],
            ['Nilai kosong', 'Nilai kosong mempertahankan nilai lama yang sudah tersimpan.'],
            ['Nilai nol', 'Nilai 0 adalah nilai yang sah dan akan disimpan.'],
            ['Menghapus nilai', 'Gunakan input nilai manual pada aplikasi untuk menghapus nilai yang tersimpan.'],
            ['Rentang', 'Nilai harus berupa angka 0 sampai 100.'],
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Keterangan', 'Informasi'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['B3:B1000' => NumberFormat::FORMAT_TEXT];
    }

    public function title(): string
    {
        return 'Petunjuk';
    }
}
