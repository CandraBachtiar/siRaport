<?php

namespace App\Models;

use Database\Factories\DeskripsiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nilai_id', 'rekomendasi', 'deskripsi_akhir', 'status'])]
class Deskripsi extends Model
{
    /** @use HasFactory<DeskripsiFactory> */
    use HasFactory;

    protected $table = 'deskripsi';

    public function nilai(): BelongsTo
    {
        return $this->belongsTo(Nilai::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }
}
