<?php

namespace App\Http\Requests;

use App\Models\Penilaian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePenilaianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('penilaian');

        return $assessment instanceof Penilaian && ($this->user()?->can('update', $assessment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['tugas', 'ulangan_harian', 'uts', 'uas'])],
            'tanggal' => ['required', 'date'],
            'bobot' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'urutan' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama penilaian wajib diisi.',
            'nama.max' => 'Nama penilaian maksimal 255 karakter.',
            'jenis.required' => 'Jenis penilaian wajib dipilih.',
            'jenis.in' => 'Jenis penilaian tidak valid.',
            'tanggal.required' => 'Tanggal penilaian wajib diisi.',
            'tanggal.date' => 'Tanggal penilaian tidak valid.',
            'bobot.numeric' => 'Bobot harus berupa angka.',
            'bobot.min' => 'Bobot harus lebih besar dari 0.',
            'bobot.max' => 'Bobot maksimal 100.',
            'urutan.required' => 'Urutan penilaian wajib diisi.',
            'urutan.integer' => 'Urutan penilaian harus berupa bilangan bulat.',
            'urutan.min' => 'Urutan penilaian minimal 1.',
            'urutan.max' => 'Urutan penilaian maksimal 999.',
        ];
    }
}
