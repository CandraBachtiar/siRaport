<?php

namespace App\Services;

use App\Models\Penilaian;
use Illuminate\Support\Collection;

class NilaiAkhirSimulasiService
{
    public const METODE_RATA_RATA = 'rata_rata';

    public const METODE_PEMBOBOTAN = 'pembobotan';

    public const METODE_PERSENTASE = 'persentase';

    /** @var array<string, string> */
    public const METODE_LABELS = [
        self::METODE_RATA_RATA => 'Rata-rata hasil TP',
        self::METODE_PEMBOBOTAN => 'Pembobotan hasil TP',
        self::METODE_PERSENTASE => 'Persentase TP tercapai',
    ];

    /**
     * @param  Collection<int, array<string, mixed>>  $goalSummaries
     * @param  Collection<int, Penilaian>  $assessments
     * @param  array<int|string, int|float|string|null>  $weights
     * @return array{
     *     status: string,
     *     nilai: float|null,
     *     metode: string|null,
     *     metodeLabel: string|null,
     *     message: string,
     *     components: Collection<int, array<string, mixed>>,
     *     reasons: Collection<int, string>,
     *     isReady: bool
     * }
     */
    public function simulate(
        Collection $goalSummaries,
        Collection $assessments,
        int $studentId,
        ?string $method,
        array $weights = [],
    ): array {
        $components = collect();
        $reasons = collect();

        if ($goalSummaries->isEmpty()) {
            $reasons->push('Belum ada tujuan pembelajaran yang menjadi dasar perhitungan.');
        }

        if ($assessments->contains(fn (Penilaian $assessment): bool => $assessment->tujuan_pembelajaran_id === null)) {
            $reasons->push('Ada penilaian yang belum dipetakan ke TP.');
        }

        if ($assessments->contains(fn (Penilaian $assessment): bool => $assessment->kategori_asesmen === null)) {
            $reasons->push('Ada penilaian yang belum diklasifikasikan sebagai formatif atau sumatif.');
        }

        foreach ($goalSummaries as $goalSummary) {
            $goal = $goalSummary['goal'];
            $sumativeCategories = $goalSummary['categories']->filter(
                fn (array $category): bool => ! $category['isFormative'],
            )->values();

            if ($sumativeCategories->isEmpty()) {
                $reasons->push('TP '.$goal->urutan.' belum memiliki asesmen sumatif.');

                continue;
            }

            if ($sumativeCategories->count() > 1) {
                $reasons->push('TP '.$goal->urutan.' memiliki lebih dari satu kategori sumatif; hubungan kategori perlu dipastikan agar tidak dihitung ganda.');

                continue;
            }

            $category = $sumativeCategories->first();
            $result = $category['rows']->get($studentId);

            if ($result === null) {
                $reasons->push('Hasil TP '.$goal->urutan.' tidak tersedia untuk siswa ini.');

                continue;
            }

            $components->push([
                'goalId' => $goal->id,
                'goalOrder' => $goal->urutan,
                'goalText' => $goal->tujuan,
                'category' => $category['label'],
                'score' => $result['score'],
                'attainment' => $result['attainment'],
                'method' => $goal->metode_rekap,
                'kktp' => (float) $goal->kktp,
                'filledCount' => $result['filledCount'],
                'totalCount' => $result['totalCount'],
                'assessmentScores' => $category['assessments']->map(fn (Penilaian $assessment): array => [
                    'name' => $assessment->nama,
                    'score' => $result['scores']->get($assessment->id),
                ])->values(),
                'weight' => null,
            ]);

            if (! $result['isComplete'] || $result['score'] === null || ! is_bool($result['attainment'])) {
                $reasons->push('Nilai sumatif TP '.$goal->urutan.' belum lengkap atau metode rekap TP belum dipilih.');
            }
        }

        if ($components->count() !== $goalSummaries->count()) {
            $reasons->push('Belum semua TP memiliki satu hasil sumatif yang dapat dibandingkan.');
        }

        if ($method === null) {
            return $this->result(
                'belum_ditetapkan',
                null,
                null,
                'Nilai akhir belum ditetapkan. Pilih metode hanya untuk melihat simulasi; hasil ini tidak disimpan sebagai nilai resmi.',
                $components,
                $reasons->unique()->values(),
                false,
            );
        }

        if (! array_key_exists($method, self::METODE_LABELS)) {
            return $this->result(
                'konfigurasi_tidak_valid',
                null,
                $method,
                'Metode simulasi tidak dikenal.',
                $components,
                $reasons->unique()->values(),
                false,
            );
        }

        if ($reasons->isNotEmpty()) {
            return $this->result(
                'belum_lengkap',
                null,
                $method,
                'Simulasi belum dapat dihitung karena sumber nilai belum lengkap atau belum jelas.',
                $components,
                $reasons->unique()->values(),
                false,
            );
        }

        if ($method === self::METODE_RATA_RATA) {
            $value = round((float) $components->avg('score'), 2);
            $message = 'Simulasi rata-rata setiap TP dihitung satu kali. Nilai ini belum menjadi nilai akhir resmi.';
        } elseif ($method === self::METODE_PEMBOBOTAN) {
            $weightValidation = $this->validateWeights($components, $weights);

            if ($weightValidation !== null) {
                return $this->result(
                    'konfigurasi_tidak_valid',
                    null,
                    $method,
                    $weightValidation,
                    $components,
                    collect(),
                    false,
                );
            }

            $components = $components->map(function (array $component) use ($weights): array {
                $component['weight'] = (float) $weights[$component['goalId']];

                return $component;
            });
            $value = round((float) $components->sum(fn (array $component): float => $component['score'] * $component['weight'] / 100), 2);
            $message = 'Simulasi pembobotan hasil TP dihitung dengan bobot yang dimasukkan dan berjumlah 100%. Nilai ini belum menjadi nilai akhir resmi.';
        } else {
            $attainedCount = $components->where('attainment', true)->count();
            $value = round(($attainedCount / $components->count()) * 100, 2);
            $message = 'Simulasi persentase dihitung dari TP yang mencapai KKTP angka. Pastikan pendekatan ini sesuai dengan kriteria sekolah; hasil ini belum menjadi nilai akhir resmi.';
        }

        return $this->result(
            'simulasi',
            $value,
            $method,
            $message,
            $components,
            collect(),
            true,
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $components
     * @param  array<int|string, int|float|string|null>  $weights
     */
    private function validateWeights(Collection $components, array $weights): ?string
    {
        $goalIds = $components->pluck('goalId')->map(fn (int|string $id): string => (string) $id)->sort()->values();
        $weightIds = collect(array_keys($weights))->map(fn (int|string $id): string => (string) $id)->sort()->values();

        if ($goalIds->all() !== $weightIds->all()) {
            return 'Isi bobot untuk setiap TP tepat satu kali sebelum menghitung simulasi.';
        }

        foreach ($weights as $weight) {
            if (! is_numeric($weight) || (float) $weight < 0 || (float) $weight > 100) {
                return 'Setiap bobot TP harus berupa angka antara 0 dan 100.';
            }
        }

        $totalWeight = round(collect($weights)->sum(fn (int|float|string|null $weight): float => (float) $weight), 2);

        if ($totalWeight !== 100.0) {
            return 'Jumlah bobot seluruh TP harus tepat 100%. Jumlah saat ini: '
                .number_format($totalWeight, 2, ',', '.').'%.';
        }

        return null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $components
     * @param  Collection<int, string>  $reasons
     * @return array{
     *     status: string,
     *     nilai: float|null,
     *     metode: string|null,
     *     metodeLabel: string|null,
     *     message: string,
     *     components: Collection<int, array<string, mixed>>,
     *     reasons: Collection<int, string>,
     *     isReady: bool
     * }
     */
    private function result(
        string $status,
        ?float $value,
        ?string $method,
        string $message,
        Collection $components,
        Collection $reasons,
        bool $isReady,
    ): array {
        return [
            'status' => $status,
            'nilai' => $value,
            'metode' => $method,
            'metodeLabel' => $method === null ? null : (self::METODE_LABELS[$method] ?? null),
            'message' => $message,
            'components' => $components,
            'reasons' => $reasons,
            'isReady' => $isReady,
        ];
    }
}
