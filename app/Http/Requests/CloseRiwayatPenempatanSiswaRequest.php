<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloseRiwayatPenempatanSiswaRequest extends FormRequest
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
            'berakhir_pada' => ['required', 'date_format:Y-m-d'],
            'alasan' => ['required', 'string', 'min:3', 'max:2000'],
            'referensi_bukti' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'berakhir_pada.required' => 'Tanggal akhir wajib diisi untuk menutup penempatan.',
            'berakhir_pada.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD.',
            'alasan.required' => 'Alasan penutupan wajib diisi.',
            'referensi_bukti.required' => 'Referensi dokumen bukti wajib diisi.',
        ];
    }
}
