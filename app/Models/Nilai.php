<?php

namespace App\Models;

use Database\Factories\NilaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'pengampu_id', 'tugas', 'ulangan_harian', 'uts', 'uas', 'nilai_akhir'])]
class Nilai extends Model
{
    /** @use HasFactory<NilaiFactory> */
    use HasFactory;

    protected $table = 'nilai';

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tugas' => 'decimal:2',
            'ulangan_harian' => 'decimal:2',
            'uts' => 'decimal:2',
            'uas' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
        ];
    }
}
