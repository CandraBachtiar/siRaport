<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\RiwayatPenempatanSiswaAudit;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PlacementHistoryManager
{
    public function record(
        User $actor,
        int $studentId,
        int $classId,
        int $schoolYearId,
        string $startDate,
        ?string $endDate,
        string $reason,
        string $evidenceReference,
    ): RiwayatPenempatanSiswa {
        $this->authorizeAdmin($actor);
        $this->validateInput($startDate, $endDate, $reason, $evidenceReference);

        return DB::transaction(function () use ($actor, $studentId, $classId, $schoolYearId, $startDate, $endDate, $reason, $evidenceReference): RiwayatPenempatanSiswa {
            $student = Siswa::query()->whereKey($studentId)->lockForUpdate()->first();

            if ($student === null) {
                $this->invalid('siswa_id', 'Siswa yang dipilih tidak ditemukan.');
            }

            $this->lockClassAndPeriod($classId, $schoolYearId);
            $this->ensureNoOverlap($studentId, $startDate, $endDate);

            $placement = RiwayatPenempatanSiswa::query()->create([
                'siswa_id' => $studentId,
                'kelas_id' => $classId,
                'tahun_ajaran_id' => $schoolYearId,
                'mulai_pada' => $startDate,
                'berakhir_pada' => $endDate,
            ]);

            $this->writeAudit($placement, $actor, 'pencatatan', null, $this->snapshot($placement), $reason, $evidenceReference);

            return $placement;
        });
    }

    public function close(
        User $actor,
        int $placementId,
        string $endDate,
        string $reason,
        string $evidenceReference,
    ): RiwayatPenempatanSiswa {
        $this->authorizeAdmin($actor);
        Validator::make([
            'berakhir_pada' => $endDate,
            'alasan' => $reason,
            'referensi_bukti' => $evidenceReference,
        ], [
            'berakhir_pada' => ['required', 'date_format:Y-m-d'],
            'alasan' => ['required', 'string', 'min:3', 'max:2000'],
            'referensi_bukti' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'berakhir_pada.required' => 'Tanggal akhir wajib diisi untuk menutup penempatan.',
            'berakhir_pada.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD.',
            'alasan.required' => 'Alasan pencatatan atau koreksi wajib diisi.',
            'referensi_bukti.required' => 'Referensi dokumen bukti wajib diisi.',
        ])->validate();

        return DB::transaction(function () use ($actor, $placementId, $endDate, $reason, $evidenceReference): RiwayatPenempatanSiswa {
            $studentId = RiwayatPenempatanSiswa::query()->whereKey($placementId)->value('siswa_id');

            if ($studentId === null) {
                $this->invalid('riwayat_penempatan_siswa_id', 'Riwayat penempatan yang dipilih tidak ditemukan.');
            }

            Siswa::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();
            $placement = RiwayatPenempatanSiswa::query()->whereKey($placementId)->lockForUpdate()->firstOrFail();

            if ($placement->berakhir_pada !== null) {
                $this->invalid('berakhir_pada', 'Riwayat ini sudah ditutup. Gunakan koreksi agar perubahan tercatat.');
            }

            $this->validateInput($placement->mulai_pada->toDateString(), $endDate, $reason, $evidenceReference);
            $this->ensureNoOverlap($studentId, $placement->mulai_pada->toDateString(), $endDate, $placement->id);
            $before = $this->snapshot($placement);
            $placement->berakhir_pada = $endDate;
            $placement->save();

            $this->writeAudit($placement, $actor, 'penutupan', $before, $this->snapshot($placement), $reason, $evidenceReference);

            return $placement;
        });
    }

    public function correct(
        User $actor,
        int $placementId,
        int $classId,
        int $schoolYearId,
        string $startDate,
        ?string $endDate,
        string $reason,
        string $evidenceReference,
    ): RiwayatPenempatanSiswa {
        $this->authorizeAdmin($actor);
        $this->validateInput($startDate, $endDate, $reason, $evidenceReference);

        return DB::transaction(function () use ($actor, $placementId, $classId, $schoolYearId, $startDate, $endDate, $reason, $evidenceReference): RiwayatPenempatanSiswa {
            $studentId = RiwayatPenempatanSiswa::query()->whereKey($placementId)->value('siswa_id');

            if ($studentId === null) {
                $this->invalid('riwayat_penempatan_siswa_id', 'Riwayat penempatan yang dipilih tidak ditemukan.');
            }

            Siswa::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();
            $placement = RiwayatPenempatanSiswa::query()->whereKey($placementId)->lockForUpdate()->firstOrFail();
            $this->lockClassAndPeriod($classId, $schoolYearId);
            $this->ensureNoOverlap($studentId, $startDate, $endDate, $placement->id);
            $before = $this->snapshot($placement);

            $placement->fill([
                'kelas_id' => $classId,
                'tahun_ajaran_id' => $schoolYearId,
                'mulai_pada' => $startDate,
                'berakhir_pada' => $endDate,
            ]);
            $placement->save();

            $this->writeAudit($placement, $actor, 'koreksi', $before, $this->snapshot($placement), $reason, $evidenceReference);

            return $placement;
        });
    }

    private function authorizeAdmin(User $actor): void
    {
        if ($actor->role !== 'admin') {
            throw new AccessDeniedHttpException('Hanya Admin yang dapat mengelola riwayat penempatan siswa.');
        }
    }

    private function validateInput(string $startDate, ?string $endDate, string $reason, string $evidenceReference): void
    {
        Validator::make([
            'mulai_pada' => $startDate,
            'berakhir_pada' => $endDate,
            'alasan' => $reason,
            'referensi_bukti' => $evidenceReference,
        ], [
            'mulai_pada' => ['required', 'date_format:Y-m-d'],
            'berakhir_pada' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mulai_pada'],
            'alasan' => ['required', 'string', 'min:3', 'max:2000'],
            'referensi_bukti' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'mulai_pada.date_format' => 'Tanggal mulai harus menggunakan format YYYY-MM-DD.',
            'berakhir_pada.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD.',
            'berakhir_pada.after_or_equal' => 'Tanggal akhir tidak boleh mendahului tanggal mulai.',
            'alasan.required' => 'Alasan pencatatan atau koreksi wajib diisi.',
            'referensi_bukti.required' => 'Referensi dokumen bukti wajib diisi.',
        ])->validate();
    }

    private function lockClassAndPeriod(int $classId, int $schoolYearId): void
    {
        if (Kelas::query()->whereKey($classId)->lockForUpdate()->first() === null) {
            $this->invalid('kelas_id', 'Kelas yang dipilih tidak ditemukan.');
        }

        if (TahunAjaran::query()->whereKey($schoolYearId)->lockForUpdate()->first() === null) {
            $this->invalid('tahun_ajaran_id', 'Periode akademik yang dipilih tidak ditemukan.');
        }
    }

    private function ensureNoOverlap(int $studentId, string $startDate, ?string $endDate, ?int $exceptPlacementId = null): void
    {
        $overlaps = RiwayatPenempatanSiswa::query()
            ->where('siswa_id', $studentId)
            ->when($exceptPlacementId !== null, fn ($query) => $query->where('id', '!=', $exceptPlacementId))
            ->whereDate('mulai_pada', '<=', $endDate ?? '9999-12-31')
            ->where(function ($query) use ($startDate): void {
                $query->whereNull('berakhir_pada')
                    ->orWhereDate('berakhir_pada', '>=', $startDate);
            })
            ->exists();

        if ($overlaps) {
            $this->invalid('mulai_pada', 'Rentang tanggal bertabrakan dengan penempatan siswa lain, termasuk pada periode akademik berbeda. Periksa tanggal sebelum menyimpan.');
        }
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    private function writeAudit(
        RiwayatPenempatanSiswa $placement,
        User $actor,
        string $action,
        ?array $before,
        ?array $after,
        string $reason,
        string $evidenceReference,
    ): void {
        RiwayatPenempatanSiswaAudit::query()->create([
            'riwayat_penempatan_siswa_id' => $placement->id,
            'user_id' => $actor->id,
            'aksi' => $action,
            'alasan' => $reason,
            'referensi_bukti' => $evidenceReference,
            'data_sebelum' => $before,
            'data_sesudah' => $after,
        ]);
    }

    /** @return array<string, int|string|null> */
    private function snapshot(RiwayatPenempatanSiswa $placement): array
    {
        return [
            'siswa_id' => $placement->siswa_id,
            'kelas_id' => $placement->kelas_id,
            'tahun_ajaran_id' => $placement->tahun_ajaran_id,
            'mulai_pada' => $placement->mulai_pada?->toDateString(),
            'berakhir_pada' => $placement->berakhir_pada?->toDateString(),
        ];
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
