<?php

namespace App\Services;

use App\Imports\NilaiRowsImport;
use App\Models\Nilai;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class NilaiImportService
{
    public function __construct(private GuruScoreService $scoreService) {}

    /**
     * @return array{headerError: ?string, rows: list<array<string, mixed>>}
     */
    public function preview(UploadedFile $file, Penilaian $assessment): array
    {
        $assessment->loadMissing('pengampu:id,kelas_id');
        $sheets = Excel::toArray(new NilaiRowsImport, $file);
        $sheetRows = $sheets[0] ?? [];

        if ($sheetRows === []) {
            return ['headerError' => 'File Excel tidak berisi data.', 'rows' => []];
        }

        $headers = array_map(
            fn (mixed $header): string => Str::of((string) $header)->trim()->lower()->replace(' ', '_')->toString(),
            $sheetRows[0],
        );

        if ($headers !== ['id_penilaian', 'id_siswa', 'nis', 'nama_siswa', 'nilai']) {
            return ['headerError' => 'Format kolom tidak sesuai dengan template nilai.', 'rows' => []];
        }

        $dataRows = [];
        foreach (array_slice($sheetRows, 1) as $index => $sheetRow) {
            $sheetRow = array_values($sheetRow);

            if ($this->isBlankRow($sheetRow)) {
                continue;
            }

            $score = $sheetRow[4] ?? null;
            if (is_string($score)) {
                $score = trim($score);
            }

            $dataRows[] = [
                'row' => $index + 2,
                'assessment_id' => trim((string) ($sheetRow[0] ?? '')),
                'student_id' => trim((string) ($sheetRow[1] ?? '')),
                'nis_reference' => trim((string) ($sheetRow[2] ?? '')),
                'name_reference' => trim((string) ($sheetRow[3] ?? '')),
                'score' => $score === '' ? null : $score,
            ];
        }

        if ($dataRows === []) {
            return ['headerError' => 'File Excel tidak berisi baris siswa.', 'rows' => []];
        }

        $studentIdCounts = array_count_values(array_filter(
            array_column($dataRows, 'student_id'),
            fn (string $studentId): bool => ctype_digit($studentId),
        ));
        $studentIds = array_values(array_unique(array_map(
            fn (string $studentId): int => (int) $studentId,
            array_filter(array_column($dataRows, 'student_id'), fn (string $studentId): bool => ctype_digit($studentId)),
        )));
        $students = $studentIds === []
            ? collect()
            : Siswa::query()
                ->where('kelas_id', $assessment->pengampu->kelas_id)
                ->whereIn('id', $studentIds)
                ->get(['id', 'nis', 'nama'])
                ->keyBy('id');
        $existingScores = $studentIds === []
            ? collect()
            : Nilai::query()
                ->where('penilaian_id', $assessment->id)
                ->whereIn('siswa_id', $studentIds)
                ->get(['siswa_id', 'nilai'])
                ->keyBy('siswa_id');

        $previewRows = [];
        foreach ($dataRows as $row) {
            $errors = Validator::make(
                [
                    'assessment_id' => $row['assessment_id'],
                    'student_id' => $row['student_id'],
                    'score' => $row['score'],
                ],
                [
                    'assessment_id' => ['required', 'integer', 'min:1'],
                    'student_id' => ['required', 'integer', 'min:1'],
                    'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
                ],
                [
                    'assessment_id.required' => 'ID penilaian wajib diisi.',
                    'assessment_id.integer' => 'ID penilaian harus berupa angka.',
                    'student_id.required' => 'ID siswa wajib diisi.',
                    'student_id.integer' => 'ID siswa harus berupa angka.',
                    'score.numeric' => 'Nilai harus berupa angka atau dibiarkan kosong.',
                    'score.min' => 'Nilai minimal 0.',
                    'score.max' => 'Nilai maksimal 100.',
                ],
            )->errors()->all();

            $studentId = ctype_digit($row['student_id']) ? (int) $row['student_id'] : null;
            $student = $studentId === null ? null : $students->get($studentId);

            if ($row['assessment_id'] !== (string) $assessment->id) {
                $errors[] = 'ID penilaian tidak sesuai dengan penilaian yang dipilih.';
            }

            if ($student !== null && ($studentIdCounts[$row['student_id']] ?? 0) > 1) {
                $errors[] = 'ID siswa duplikat pada file.';
            }

            if ($student === null && $studentId !== null) {
                $errors[] = 'ID siswa tidak ditemukan pada kelas penilaian ini.';
            }

            $existingScore = $student === null ? null : $existingScores->get($student->id)?->nilai;
            $scoreIsBlank = $row['score'] === null;
            $valid = $errors === [];

            $previewRows[] = [
                'row' => $row['row'],
                'assessment_id' => $row['assessment_id'],
                'student_id' => $studentId,
                'student_name' => $student?->nama ?? ($row['name_reference'] ?: 'ID '.$row['student_id']),
                'nis' => $student?->nis ?? $row['nis_reference'],
                'score' => $scoreIsBlank || ! is_numeric($row['score']) ? null : (float) $row['score'],
                'score_is_blank' => $scoreIsBlank,
                'existing_score' => $existingScore === null ? null : (float) $existingScore,
                'valid' => $valid,
                'will_import' => $valid && ! $scoreIsBlank,
                'message' => $errors !== []
                    ? implode(' ', array_unique($errors))
                    : ($scoreIsBlank ? 'Nilai kosong; nilai lama tetap dipertahankan.' : 'Siap disimpan.'),
            ];
        }

        return ['headerError' => null, 'rows' => $previewRows];
    }

    /** @param list<array<string, mixed>> $rows */
    public function import(Penilaian $assessment, array $rows): int
    {
        if ($rows === [] || collect($rows)->contains(fn (array $row): bool => ! ($row['valid'] ?? false))) {
            throw ValidationException::withMessages(['import' => 'Periksa semua baris pada pratinjau sebelum menyimpan.']);
        }

        $scores = collect($rows)
            ->filter(fn (array $row): bool => ($row['will_import'] ?? false) === true)
            ->mapWithKeys(fn (array $row): array => [(int) $row['student_id'] => (float) $row['score']])
            ->all();

        if ($scores === []) {
            throw ValidationException::withMessages(['import' => 'Tidak ada nilai terisi untuk disimpan. Sel kosong tidak mengubah nilai lama.']);
        }

        return DB::transaction(function () use ($assessment, $rows, $scores): int {
            $assessment->loadMissing('pengampu:id,kelas_id');
            $studentIds = array_keys($scores);
            $currentClassStudentIds = Siswa::query()
                ->where('kelas_id', $assessment->pengampu->kelas_id)
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn (int|string $studentId): int => (int) $studentId)
                ->all();

            if (count($currentClassStudentIds) !== count($scores)) {
                throw ValidationException::withMessages(['import' => 'Keanggotaan kelas berubah sejak pratinjau. Periksa ulang file dan buat pratinjau kembali.']);
            }

            foreach ($rows as $row) {
                if (($row['will_import'] ?? false) && (int) $row['assessment_id'] !== $assessment->id) {
                    throw ValidationException::withMessages(['import' => 'Penilaian pada pratinjau tidak sesuai. Buat pratinjau kembali.']);
                }
            }

            return $this->scoreService->save($assessment, $scores);
        });
    }

    /** @param array<int, mixed> $row */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
