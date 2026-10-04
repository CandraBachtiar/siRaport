<?php

namespace App\Models;

use Database\Factories\PenilaianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pengampu_id', 'nama', 'jenis', 'tanggal', 'bobot', 'urutan'])]
class Penilaian extends Model
{
    /** @use HasFactory<PenilaianFactory> */
    use HasFactory;

    protected $table = 'penilaian';

    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(Pengampu::class);
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
