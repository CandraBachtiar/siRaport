<?php

namespace App\Models;

use Database\Factories\TujuanPembelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pengampu_id', 'urutan', 'tujuan', 'kktp', 'metode_rekap'])]
class TujuanPembelajaran extends Model
{
    public const METODE_RATA_RATA = 'rata_rata';

    public const METODE_NILAI_TERTINGGI = 'nilai_tertinggi';

    /** @var array<string, string> */
    public const METODE_REKAP_LABELS = [
        self::METODE_RATA_RATA => 'Rata-rata',
        self::METODE_NILAI_TERTINGGI => 'Nilai tertinggi',
    ];

    /** @use HasFactory<TujuanPembelajaranFactory> */
    use HasFactory;

    protected $table = 'tujuan_pembelajaran';

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'kktp' => 'decimal:2',
        ];
    }
}
