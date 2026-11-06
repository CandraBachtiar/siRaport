<?php

namespace App\Models;

use Database\Factories\TujuanPembelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pengampu_id', 'urutan', 'tujuan', 'kktp'])]
class TujuanPembelajaran extends Model
{
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
