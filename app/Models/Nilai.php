<?php

namespace App\Models;

use Database\Factories\NilaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['siswa_id', 'penilaian_id', 'nilai'])]
class Nilai extends Model
{
    /** @use HasFactory<NilaiFactory> */
    use HasFactory;

    protected $table = 'nilai';

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function deskripsi(): HasOne
    {
        return $this->hasOne(Deskripsi::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
        ];
    }
}
