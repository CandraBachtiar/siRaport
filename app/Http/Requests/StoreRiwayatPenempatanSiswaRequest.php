<?php

namespace App\Http\Requests;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRiwayatPenempatanSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'integer', Rule::exists(Kelas::class, 'id')],
            'tahun_ajaran_id' => ['required', 'integer', Rule::exists(TahunAjaran::class, 'id')],
            'mulai_pada' => ['required', 'date_format:Y-m-d'],
            'berakhir_pada' => ['nullable', 'date_format:Y-m-d'],
            'alasan' => ['required', 'string', 'min:3', 'max:2000'],
            'referensi_bukti' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kelas_id.required' => 'Pilih kelas penempatan.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'tahun_ajaran_id.required' => 'Pilih periode akademik.',
            'tahun_ajaran_id.exists' => 'Periode akademik yang dipilih tidak ditemukan.',
            'mulai_pada.required' => 'Tanggal mulai wajib diisi.',
            'mulai_pada.date_format' => 'Tanggal mulai harus menggunakan format YYYY-MM-DD.',
            'berakhir_pada.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD.',
            'alasan.required' => 'Alasan pencatatan wajib diisi.',
            'referensi_bukti.required' => 'Referensi dokumen bukti wajib diisi.',
        ];
    }
}
