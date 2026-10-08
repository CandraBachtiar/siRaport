<?php

namespace App\Http\Requests;

use App\Models\Penilaian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveNilaiRequest extends FormRequest
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
            'nilai' => ['required', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nilai.required' => 'Daftar nilai siswa tidak ditemukan.',
            'nilai.array' => 'Format daftar nilai tidak valid.',
            'nilai.*.numeric' => 'Nilai siswa harus berupa angka.',
            'nilai.*.min' => 'Nilai siswa minimal 0.',
            'nilai.*.max' => 'Nilai siswa maksimal 100.',
        ];
    }
}
