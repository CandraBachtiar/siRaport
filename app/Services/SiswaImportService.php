<?php

namespace App\Services;

use App\Imports\SiswaRowsImport;
use App\Models\Kelas;
use App\Models\Siswa;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SiswaImportService
{
    /**
     * @return array{headerError: ?string, rows: array<int, array<string, mixed>>}
     */
    public function preview(UploadedFile $file): array
    {
        $import = new SiswaRowsImport;
        $sheets = Excel::toArray($import, $file);
        $rows = $sheets[0] ?? [];

        if ($rows === []) {
            return ['headerError' => 'File Excel tidak berisi data.', 'rows' => []];
        }

        $headers = array_map(
            fn (mixed $header): string => Str::of((string) $header)->trim()->lower()->replace(' ', '_')->toString(),
            $rows[0],
        );

        if ($headers !== ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tempat_lahir', 'tanggal_lahir', 'alamat']) {
            return ['headerError' => 'Format kolom Excel tidak sesuai dengan template.', 'rows' => []];
        }

        $dataRows = array_slice($rows, 1);
        $preparedRows = [];
        foreach ($dataRows as $index => $row) {
            $row = array_values($row);
            if ($this->isBlankRow($row)) {
                continue;
            }

            $kelasValue = trim((string) ($row[4] ?? ''));
            $kelasId = ctype_digit($kelasValue) ? (int) $kelasValue : null;
            $preparedRows[] = [
                'row' => $index + 2,
                'nis' => trim((string) ($row[0] ?? '')),
                'nisn' => trim((string) ($row[1] ?? '')) ?: null,
                'name' => trim((string) ($row[2] ?? '')),
                'gender' => trim((string) ($row[3] ?? '')),
                'kelas_id' => $kelasId,
                'kelas_value' => $kelasValue,
                'birth_place' => trim((string) ($row[5] ?? '')) ?: null,
                'birth_date' => $this->normalizeDate($row[6] ?? null),
                'address' => trim((string) ($row[7] ?? '')) ?: null,
            ];
        }

        if ($preparedRows === []) {
            return ['headerError' => 'File Excel tidak berisi data.', 'rows' => []];
        }

        $nisValues = array_values(array_unique(array_filter(array_column($preparedRows, 'nis'), fn (string $value): bool => $value !== '')));
        $nisnValues = array_values(array_unique(array_filter(array_column($preparedRows, 'nisn'), fn (?string $value): bool => $value !== null)));
        $classIds = array_values(array_unique(array_filter(array_column($preparedRows, 'kelas_id'), fn (?int $value): bool => $value !== null)));

        $existingNis = $nisValues === []
            ? []
            : Siswa::query()->whereIn('nis', $nisValues)->pluck('nis')->flip()->all();
        $existingNisn = $nisnValues === []
            ? []
            : Siswa::query()->whereIn('nisn', $nisnValues)->whereNotNull('nisn')->pluck('nisn')->flip()->all();
        $kelasById = $classIds === []
            ? collect()
            : Kelas::query()->whereIn('id', $classIds)->get(['id', 'tingkat', 'nama'])->keyBy('id');

        $seenNis = [];
        $seenNisn = [];
        $previewRows = [];

        foreach ($preparedRows as $row) {
            $errors = Validator::make(
                [
                    'nis' => $row['nis'],
                    'nisn' => $row['nisn'],
                    'name' => $row['name'],
                    'gender' => $row['gender'],
                    'kelas_id' => $row['kelas_id'],
                    'birth_place' => $row['birth_place'],
                    'birth_date' => $row['birth_date'],
                    'address' => $row['address'],
                ],
                [
                    'nis' => ['required', 'string', 'max:255'],
                    'nisn' => ['nullable', 'string', 'max:255'],
                    'name' => ['required', 'string', 'max:255'],
                    'gender' => ['required', 'in:L,P'],
                    'kelas_id' => ['required', 'integer'],
                    'birth_place' => ['nullable', 'string', 'max:255'],
                    'birth_date' => ['nullable', 'date_format:Y-m-d'],
                    'address' => ['nullable', 'string'],
                ],
                [
                    'nis.required' => 'NIS wajib diisi.',
                    'nisn.max' => 'NISN maksimal 255 karakter.',
                    'name.required' => 'Nama siswa wajib diisi.',
                    'gender.required' => 'Jenis kelamin wajib diisi.',
                    'gender.in' => 'Jenis kelamin harus bernilai L atau P.',
                    'kelas_id.required' => 'Kelas wajib diisi dengan ID pada lembar Daftar Kelas.',
                    'birth_place.max' => 'Tempat lahir maksimal 255 karakter.',
                    'birth_date.date_format' => 'Tanggal lahir harus berupa tanggal valid dengan format YYYY-MM-DD atau tanggal Excel.',
                ],
            )->errors()->all();

            $nis = $row['nis'];
            $nisn = $row['nisn'];
            $kelasId = $row['kelas_id'];
            if ($nis !== '' && isset($existingNis[$nis])) {
                $errors[] = 'NIS sudah terdaftar.';
            }
            if ($nis !== '' && isset($seenNis[$nis])) {
                $errors[] = 'NIS duplikat pada file.';
            }
            if ($nisn !== null && isset($existingNisn[$nisn])) {
                $errors[] = 'NISN sudah terdaftar.';
            }
            if ($nisn !== null && isset($seenNisn[$nisn])) {
                $errors[] = 'NISN duplikat pada file.';
            }
            if ($kelasId === null || ! $kelasById->has($kelasId)) {
                $errors[] = 'ID kelas tidak ditemukan. Gunakan ID pada lembar Daftar Kelas.';
            }

            if ($nis !== '') {
                $seenNis[$nis] = true;
            }
            if ($nisn !== null) {
                $seenNisn[$nisn] = true;
            }

            $kelas = $kelasId === null ? null : $kelasById->get($kelasId);
            $previewRows[] = [
                'row' => $row['row'],
                'nis' => $nis,
                'nisn' => $nisn,
                'name' => $row['name'],
                'gender' => $row['gender'],
                'kelas_id' => $kelasId,
                'kelas_label' => $kelas === null ? 'Tidak ditemukan (ID: '.$row['kelas_value'].')' : $kelas->tingkat.' '.$kelas->nama.' (ID: '.$kelas->id.')',
                'birth_place' => $row['birth_place'],
                'birth_date' => $row['birth_date'],
                'address' => $row['address'],
                'valid' => $errors === [],
                'message' => implode(' ', array_unique($errors)),
            ];
        }

        return ['headerError' => null, 'rows' => $previewRows];
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function import(array $rows): int
    {
        if ($rows === [] || collect($rows)->contains(fn (array $row): bool => ! ($row['valid'] ?? false))) {
            throw ValidationException::withMessages(['import' => 'Periksa kembali semua data sebelum melakukan import.']);
        }

        return DB::transaction(function () use ($rows): int {
            $nisValues = array_values(array_unique(array_column($rows, 'nis')));
            $nisnValues = array_values(array_unique(array_filter(array_column($rows, 'nisn'), fn (?string $value): bool => $value !== null)));
            $classIds = array_values(array_unique(array_column($rows, 'kelas_id')));

            $existingNis = $nisValues === []
                ? []
                : Siswa::query()->whereIn('nis', $nisValues)->lockForUpdate()->pluck('nis')->all();
            $existingNisn = $nisnValues === []
                ? []
                : Siswa::query()->whereIn('nisn', $nisnValues)->whereNotNull('nisn')->lockForUpdate()->pluck('nisn')->all();
            $existingClassIds = Kelas::query()->whereIn('id', $classIds)->lockForUpdate()->pluck('id')->all();

            if ($existingNis !== [] || $existingNisn !== []) {
                throw ValidationException::withMessages(['import' => 'NIS atau NISN sudah digunakan sejak preview. Periksa ulang file sebelum mengimpor.']);
            }

            if (count($existingClassIds) !== count($classIds)) {
                throw ValidationException::withMessages(['import' => 'Ada kelas yang berubah sejak preview. Periksa ulang file sebelum mengimpor.']);
            }

            $timestamp = now();
            $studentRows = array_map(fn (array $row): array => [
                'kelas_id' => $row['kelas_id'],
                'nis' => $row['nis'],
                'nisn' => $row['nisn'],
                'nama' => $row['name'],
                'jenis_kelamin' => $row['gender'],
                'tempat_lahir' => $row['birth_place'],
                'tanggal_lahir' => $row['birth_date'],
                'alamat' => $row['address'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $rows);

            Siswa::query()->insert($studentRows);

            return count($studentRows);
        });
    }

    /** @param array<int, mixed> $row */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value instanceof DateTimeInterface || (is_scalar($value) && trim((string) $value) !== '')) {
                return false;
            }
        }

        return true;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_scalar($value)) {
            return '';
        }

        if (trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial < 1 || $serial > 2_958_465) {
                return (string) $value;
            }

            try {
                return Date::excelToDateTimeObject($serial)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        $date = trim((string) $value);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $parsed->format('Y-m-d') !== $date) {
            return $date;
        }

        return $parsed->format('Y-m-d');
    }
}
