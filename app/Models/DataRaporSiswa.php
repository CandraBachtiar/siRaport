<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['siswa_id', 'tahun_ajaran_id', 'sakit', 'izin', 'tanpa_keterangan', 'catatan_wali_kelas', 'keputusan_kenaikan', 'keputusan_oleh'])]
class DataRaporSiswa extends Model
{
    protected $table = 'data_rapor_siswa';

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keputusan_oleh');
    }
}
