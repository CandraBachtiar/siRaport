<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ScoreTrendService
{
    /**
     * @param  Collection<int, int|float|null>  $scores
     * @return array{label: string, difference: float|null, explanation: string}
     */
    public function summarize(Collection $scores): array
    {
        $filledScores = $scores
            ->filter(fn (int|float|null $score): bool => $score !== null)
            ->map(fn (int|float $score): float => (float) $score)
            ->values();

        if ($filledScores->isEmpty()) {
            return [
                'label' => 'Belum ada data',
                'difference' => null,
                'explanation' => 'Belum ada nilai yang dapat digunakan untuk membaca tren.',
            ];
        }

        if ($filledScores->count() < 4) {
            return [
                'label' => 'Belum cukup data',
                'difference' => null,
                'explanation' => 'Diperlukan sedikitnya empat nilai agar tren membandingkan beberapa nilai pada tiap periode.',
            ];
        }

        $windowSize = min(3, intdiv($filledScores->count(), 2));
        $previousAverage = (float) $filledScores->slice(0, $windowSize)->average();
        $latestAverage = (float) $filledScores->slice(-$windowSize)->average();
        $difference = round($latestAverage - $previousAverage, 2);

        return [
            'label' => $difference > 2 ? 'Meningkat' : ($difference < -2 ? 'Menurun' : 'Stabil'),
            'difference' => $difference,
            'explanation' => 'Rata-rata '.$windowSize.' nilai terbaru '.number_format($latestAverage, 2, ',', '.')
                .', dibanding '.$windowSize.' nilai sebelumnya '.number_format($previousAverage, 2, ',', '.').'.',
        ];
    }
}
