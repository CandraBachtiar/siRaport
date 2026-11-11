<?php

namespace App\Http\Requests;

use App\Models\Pengampu;
use App\Models\TujuanPembelajaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTujuanPembelajaranRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assignment = $this->route('pengampu');

        return $assignment instanceof Pengampu
            && ($this->user()?->can('view', $assignment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assignment = $this->route('pengampu');

        return [
            'urutan' => [
                'required', 'integer', 'min:1', 'max:999',
                Rule::unique('tujuan_pembelajaran', 'urutan')->where('pengampu_id', $assignment?->id),
            ],
            'tujuan' => ['required', 'string', 'max:5000'],
            'kktp' => ['required', 'numeric', 'min:0', 'max:100'],
            'metode_rekap' => ['required', Rule::in(array_keys(TujuanPembelajaran::METODE_REKAP_LABELS))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'urutan.required' => 'Nomor TP wajib diisi.',
            'urutan.integer' => 'Nomor TP harus berupa bilangan bulat.',
            'urutan.min' => 'Nomor TP minimal 1.',
            'urutan.max' => 'Nomor TP maksimal 999.',
            'urutan.unique' => 'Nomor TP tersebut sudah digunakan pada tugas mengajar ini.',
            'tujuan.required' => 'Rumusan tujuan pembelajaran wajib diisi.',
            'tujuan.max' => 'Rumusan tujuan pembelajaran maksimal 5000 karakter.',
            'kktp.required' => 'KKTP wajib diisi.',
            'kktp.numeric' => 'KKTP harus berupa angka.',
            'kktp.min' => 'KKTP minimal 0.',
            'kktp.max' => 'KKTP maksimal 100.',
            'metode_rekap.required' => 'Metode rekap TP wajib dipilih.',
            'metode_rekap.in' => 'Metode rekap TP tidak valid.',
        ];
    }
}
