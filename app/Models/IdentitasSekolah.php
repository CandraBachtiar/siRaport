<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama_sekolah', 'npsn', 'alamat', 'nama_kepala_sekolah', 'nip_kepala_sekolah'])]
class IdentitasSekolah extends Model
{
    protected $table = 'identitas_sekolah';
}
