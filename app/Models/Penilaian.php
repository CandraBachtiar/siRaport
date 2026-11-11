<?php

namespace App\Models;

use Database\Factories\PenilaianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pengampu_id', 'tujuan_pembelajaran_id', 'nama', 'jenis', 'kategori_asesmen', 'tanggal', 'bobot', 'urutan'])]
class Penilaian extends Model
{
    public const KATEGORI_FORMATIF = 'formatif';

    public const KATEGORI_LINGKUP_MATERI = 'lingkup_materi';

    public const KATEGORI_PTS = 'pts';

    public const KATEGORI_AKHIR_SEMESTER = 'asesmen_akhir_semester';

    /** @var array<string, string> */
    public const KATEGORI_LABELS = [
        self::KATEGORI_FORMATIF => 'Formatif',
        self::KATEGORI_LINGKUP_MATERI => 'Sumatif Lingkup Materi',
        self::KATEGORI_PTS => 'Penilaian Tengah Semester (PTS)',
        self::KATEGORI_AKHIR_SEMESTER => 'Asesmen Akhir Semester',
    ];

    /** @var array<string, string> */
    public const KATEGORI_SUMATIF_LABELS = [
        self::KATEGORI_LINGKUP_MATERI => 'Sumatif Lingkup Materi',
        self::KATEGORI_PTS => 'Penilaian Tengah Semester (PTS)',
        self::KATEGORI_AKHIR_SEMESTER => 'Asesmen Akhir Semester',
    ];

    /** @use HasFactory<PenilaianFactory> */
    use HasFactory;

    protected $table = 'penilaian';

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'bobot' => 'decimal:2',
            'urutan' => 'integer',
        ];
    }
}
