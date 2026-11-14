<?php

namespace App\Models;

use Database\Factories\RiwayatPenempatanSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'kelas_id', 'tahun_ajaran_id', 'mulai_pada', 'berakhir_pada'])]
class RiwayatPenempatanSiswa extends Model
{
    /** @use HasFactory<RiwayatPenempatanSiswaFactory> */
    use HasFactory;

    protected $table = 'riwayat_penempatan_siswa';

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mulai_pada' => 'date',
            'berakhir_pada' => 'date',
        ];
    }
}
