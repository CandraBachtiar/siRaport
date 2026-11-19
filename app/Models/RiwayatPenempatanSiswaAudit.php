<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'riwayat_penempatan_siswa_id',
    'user_id',
    'aksi',
    'alasan',
    'referensi_bukti',
    'data_sebelum',
    'data_sesudah',
])]
class RiwayatPenempatanSiswaAudit extends Model
{
    protected $table = 'riwayat_penempatan_siswa_audit';

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Jejak audit riwayat penempatan tidak dapat diubah.');
        });

        static::deleting(function (): never {
            throw new LogicException('Jejak audit riwayat penempatan tidak dapat dihapus.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
        ];
    }
}
