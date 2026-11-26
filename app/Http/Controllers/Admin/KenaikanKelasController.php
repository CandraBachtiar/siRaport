
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataRaporSiswa;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\RiwayatPenempatanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\PlacementHistoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    // Menampilkan halaman pengelolaan kenaikan kelas
    public function index(Request $request): View
    {
        $data = $request->validate([
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajaran,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'tanggal_akhir' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['nullable', 'in:semua,naik,tidak_naik,belum'],
        ]);

        $years = TahunAjaran::query()
            ->where('semester', 'Genap')
            ->orderByDesc('tahun')
            ->get();

        $classes = Kelas::query()
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();

        $year = $years->firstWhere(
            'id',
            (int) ($data['tahun_ajaran_id'] ?? 0)
        );

        $class = $classes->firstWhere(
            'id',
            (int) ($data['kelas_id'] ?? 0)
        );

        $date = $data['tanggal_akhir'] ?? null;
        $status = $data['status'] ?? 'semua';

        $rows = collect();
        $nextYear = null;
        $warning = null;

        if ($year && $class && $date) {
            $nextName = $this->nextYearName($year, $date);

            $nextYear = TahunAjaran::query()
                ->where('tahun', $nextName)
                ->where('semester', 'Ganjil')
                ->first();

            // Mengambil riwayat penempatan berdasarkan tanggal
            $placements = $this->onDate($date)
                ->get()
                ->groupBy('siswa_id');

            $sourceGroups = $placements->filter(
                fn ($group) => $group->contains(
                    fn ($p) =>
                        (int) $p->tahun_ajaran_id === (int) $year->id
                        && (int) $p->kelas_id === (int) $class->id
                )
            );

            $ids = $sourceGroups->keys();

            $students = Siswa::query()
                ->whereIn('id', $ids)
                ->orderBy('nama')
                ->get();

            $records = DataRaporSiswa::query()
                ->where('tahun_ajaran_id', $year->id)
                ->whereIn('siswa_id', $ids)
                ->get()
                ->keyBy('siswa_id');

            $targets = RiwayatPenempatanSiswa::query()
                ->whereHas(
                    'tahunAjaran',
                    fn ($q) => $q->where('tahun', $nextName)
                )
                ->whereIn('siswa_id', $ids)
                ->with(['kelas', 'tahunAjaran'])
                ->get()
                ->groupBy('siswa_id');

            $rows = $students->map(fn ($student) => [
                'student' => $student,

                'decision' => $records
                    ->get($student->id)?->keputusan_kenaikan,

                'conflict' => $sourceGroups
                    ->get($student->id)->count() !== 1,

                'targets' => $targets
                    ->get($student->id, collect()),

            ])->filter(
                fn ($row) => $status === 'semua'
                    || ($row['decision'] ?? 'belum') === $status
            )->values();

            if ($sourceGroups->isEmpty()) {
                $warning = 'Tidak ditemukan riwayat penempatan yang cocok. '
                    . 'Periksa tanggal dan catat riwayat yang sah melalui menu Siswa.';
            }
        }

        return view('admin.kenaikan-kelas.index', [
            'admin' => $request->user(),
            'years' => $years,
            'classes' => $classes,
            'year' => $year,
            'class' => $class,
            'date' => $date,
            'status' => $status,
            'rows' => $rows,
            'nextYear' => $nextYear,
            'warning' => $warning,
        ]);
    }

    // Menyimpan keputusan naik atau tidak naik kelas
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            ...$this->sourceRules(),

            'keputusan_kenaikan' => [
                'required',
                'in:naik,tidak_naik'
            ],
        ]);

        DB::transaction(function () use ($request, $data): void {

            [$student, $year] = $this->lockSource($data);

            $nextName = $this->nextYearName(
                $year,
                $data['tanggal_akhir']
            );

            DataRaporSiswa::query()
                ->where('siswa_id', $student->id)
                ->where('tahun_ajaran_id', $year->id)
                ->lockForUpdate()
                ->first();

            // Mencegah perubahan keputusan setelah penempatan
            if ($this->hasNextPlacement($student->id, $nextName)) {
                $this->invalid(
                    'keputusan_kenaikan',
                    'Penempatan tahun berikutnya sudah tercatat. '
                    . 'Keputusan tidak dapat diubah melalui halaman ini.'
                );
            }

            DataRaporSiswa::query()->updateOrCreate(
                [
                    'siswa_id' => $student->id,
                    'tahun_ajaran_id' => $year->id,
                ],
                [
                    'keputusan_kenaikan' => $data['keputusan_kenaikan'],
                    'keputusan_oleh' => $request->user()->id,
                ]
            );
        });

        return $this->backToList($data)->with(
            'success',
            'Keputusan kenaikan kelas berhasil disimpan. '
                . 'Kelas aktif belum diubah.'
        );
    }

    // Mengelola penempatan siswa pada tahun ajaran berikutnya
    public function place(
        Request $request,
        PlacementHistoryManager $manager
    ): RedirectResponse {
        $data = $request->validate([
            ...$this->sourceRules(),

            'kelas_tujuan_id' => [
                'required',
                'integer',
                'exists:kelas,id'
            ],

            'mulai_pada' => [
                'required',
                'date_format:Y-m-d',
                'after:tanggal_akhir',
                'before_or_equal:today'
            ],

            'referensi_bukti' => [
                'required',
                'string',
                'min:3',
                'max:1000'
            ],

            'konfirmasi' => ['accepted'],
        ]);

        // Memastikan tabel audit sudah tersedia
        if (! Schema::hasTable('riwayat_penempatan_siswa_audit')) {
            $this->invalid(
                'referensi_bukti',
                'Migration audit riwayat penempatan belum diterapkan.'
            );
        }

        DB::transaction(function () use (
            $request,
            $manager,
            $data
        ): void {

            [$student, $year, $source] = $this->lockSource($data);

            $nextName = $this->nextYearName(
                $year,
                $data['tanggal_akhir']
            );

            $targetYear = TahunAjaran::query()
                ->where('tahun', $nextName)
                ->where('semester', 'Ganjil')
                ->lockForUpdate()
                ->first();

            // Periode tujuan harus sudah aktif
            if (! $targetYear || ! $targetYear->aktif) {
                $this->invalid(
                    'mulai_pada',
                    'Aktifkan semester ganjil tahun ajaran berikutnya '
                    . 'sebelum mengonfirmasi penempatan.'
                );
            }

            if (
                substr($data['mulai_pada'], 0, 4)
                !== substr($nextName, 0, 4)
            ) {
                $this->invalid(
                    'mulai_pada',
                    'Tanggal mulai harus berada pada tahun kalender '
                    . 'awal periode tujuan.'
                );
            }

            // Memastikan kelas aktif masih sesuai kelas asal
            if ((int) $student->kelas_id !== (int) $source->kelas_id) {
                $this->invalid(
                    'kelas_tujuan_id',
                    'Kelas aktif sudah berbeda dari kelas asal. '
                    . 'Periksa riwayat sebelum melanjutkan.'
                );
            }

            // Mencegah penempatan ganda
            if (
                $this->hasNextPlacement($student->id, $nextName)
                || RiwayatPenempatanSiswa::query()
                    ->where('siswa_id', $student->id)
                    ->whereDate('mulai_pada', '>', $data['tanggal_akhir'])
                    ->exists()
            ) {
                $this->invalid(
                    'kelas_tujuan_id',
                    'Sudah ada penempatan setelah tanggal akhir. '
                    . 'Gunakan pemeriksaan riwayat, bukan penempatan ulang.'
                );
            }

            $record = DataRaporSiswa::query()
                ->where('siswa_id', $student->id)
                ->where('tahun_ajaran_id', $year->id)
                ->lockForUpdate()
                ->first();

            $decision = $record?->keputusan_kenaikan;

            if (! in_array($decision, ['naik', 'tidak_naik'], true)) {
                $this->invalid(
                    'keputusan_kenaikan',
                    'Simpan keputusan kenaikan kelas terlebih dahulu.'
                );
            }

            $from = Kelas::query()
                ->findOrFail($source->kelas_id);

            $to = Kelas::query()
                ->lockForUpdate()
                ->findOrFail($data['kelas_tujuan_id']);

            $fromLevel = $this->level($from->tingkat);
            $toLevel = $this->level($to->tingkat);

            if ($fromLevel === null || $toLevel === null) {
                $this->invalid(
                    'kelas_tujuan_id',
                    'Tingkat kelas tidak dikenali sebagai tingkat SD 1–6.'
                );
            }

            // Naik kelas menuju tingkat berikutnya
            // Tidak naik kelas tetap pada tingkat yang sama
            $expected = $decision === 'naik'
                ? $fromLevel + 1
                : $fromLevel;

            if ($expected > 6 || $toLevel !== $expected) {
                $this->invalid(
                    'kelas_tujuan_id',
                    'Kelas tujuan tidak sesuai keputusan kenaikan. '
                    . 'Kelulusan kelas VI ditangani terpisah.'
                );
            }

            // Melindungi riwayat nilai siswa
            if (
                Nilai::query()
                    ->where('siswa_id', $student->id)
                    ->whereHas(
                        'penilaian',
                        fn ($q) => $q
                            ->whereDate('tanggal', '>', $data['tanggal_akhir'])
                            ->whereHas(
                                'pengampu',
                                fn ($a) => $a
                                    ->where('tahun_ajaran_id', $year->id)
                                    ->where('kelas_id', $source->kelas_id)
                            )
                    )
                    ->exists()
            ) {
                $this->invalid(
                    'tanggal_akhir',
                    'Masih ada nilai pada penilaian kelas asal '
                    . 'setelah tanggal akhir. Periksa tanggal penempatan.'
                );
            }

            $reason = $decision === 'naik'
                ? 'Penempatan karena naik kelas'
                : 'Penempatan karena mengulang kelas';

            // Menutup riwayat penempatan sebelumnya
            if ($source->berakhir_pada === null) {
                $manager->close(
                    $request->user(),
                    $source->id,
                    $data['tanggal_akhir'],
                    $reason,
                    $data['referensi_bukti']
                );
            } elseif (
                $source->berakhir_pada->toDateString()
                !== $data['tanggal_akhir']
            ) {
                $this->invalid(
                    'tanggal_akhir',
                    'Tanggal akhir tidak sama dengan riwayat yang sudah ditutup. '
                    . 'Periksa dokumen penempatan.'
                );
            }

            // Membuat riwayat penempatan pada periode baru
            $manager->record(
                $request->user(),
                $student->id,
                $to->id,
                $targetYear->id,
                $data['mulai_pada'],
                null,
                $reason,
                $data['referensi_bukti']
            );

            // Memperbarui kelas aktif tanpa membuat siswa baru
            $student->update([
                'kelas_id' => $to->id,
            ]);
        });

        return $this->backToList($data)->with(
            'success',
            'Penempatan dan kelas aktif berhasil diperbarui. '
                . 'Riwayat lama tetap tersimpan.'
        );
    }

    // Validasi data kelas dan periode asal
    private function sourceRules(): array
    {
        return [
            'siswa_id' => [
                'required',
                'integer',
                'exists:siswa,id'
            ],

            'tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:tahun_ajaran,id'
            ],

            'kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id'
            ],

            'tanggal_akhir' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today'
            ],
        ];
    }

    // Mencari penempatan siswa berdasarkan tanggal
    private function onDate(string $date)
    {
        return RiwayatPenempatanSiswa::query()
            ->whereDate('mulai_pada', '<=', $date)
            ->where(
                fn ($q) => $q
                    ->whereNull('berakhir_pada')
                    ->orWhereDate('berakhir_pada', '>=', $date)
            );
    }

    // Memastikan riwayat asal siswa valid
    private function lockSource(array $data): array
    {
        $student = Siswa::query()
            ->lockForUpdate()
            ->findOrFail($data['siswa_id']);

        $year = TahunAjaran::query()
            ->lockForUpdate()
            ->findOrFail($data['tahun_ajaran_id']);

        $this->nextYearName($year, $data['tanggal_akhir']);

        $placements = $this->onDate($data['tanggal_akhir'])
            ->where('siswa_id', $student->id)
            ->lockForUpdate()
            ->get();

        $source = $placements->first();

        if (
            $placements->count() !== 1
            || (int) $source->kelas_id !== (int) $data['kelas_id']
            || (int) $source->tahun_ajaran_id !== (int) $year->id
        ) {
            $this->invalid(
                'siswa_id',
                'Riwayat siswa pada kelas, periode, dan tanggal '
                . 'yang dipilih belum valid atau bertabrakan.'
            );
        }

        return [$student, $year, $source];
    }

    // Menentukan tahun ajaran berikutnya
    private function nextYearName(
        TahunAjaran $year,
        string $date
    ): string {
        if (
            $year->semester !== 'Genap'
            || ! preg_match('/^(\d{4})\/(\d{4})$/', $year->tahun, $m)
            || (int) $m[2] !== (int) $m[1] + 1
            || substr($date, 0, 4) !== $m[2]
        ) {
            $this->invalid(
                'tahun_ajaran_id',
                'Pilih semester genap yang sah dan tanggal akhir '
                . 'pada tahun kalender akhirnya.'
            );
        }

        return $m[2] . '/' . ((int) $m[2] + 1);
    }

    // Memeriksa penempatan pada tahun berikutnya
    private function hasNextPlacement(
        int $studentId,
        string $nextName
    ): bool {
        return RiwayatPenempatanSiswa::query()
            ->where('siswa_id', $studentId)
            ->whereHas(
                'tahunAjaran',
                fn ($q) => $q->where('tahun', $nextName)
            )
            ->exists();
    }

    // Mengubah tingkat kelas menjadi angka
    private function level(string $value): ?int
    {
        $value = strtoupper(trim($value));

        $value = preg_replace('/^KELAS\s+/', '', $value);

        return [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
            'VI' => 6,
        ][$value] ?? null;
    }

    // Kembali ke daftar kenaikan kelas
    private function backToList(array $data): RedirectResponse
    {
        return redirect()->route(
            'admin.keputusan-kenaikan.index',
            [
                'tahun_ajaran_id' => $data['tahun_ajaran_id'],
                'kelas_id' => $data['kelas_id'],
                'tanggal_akhir' => $data['tanggal_akhir'],
            ]
        );
    }

    // Menampilkan pesan kesalahan validasi
    private function invalid(
        string $field,
        string $message
    ): never {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
