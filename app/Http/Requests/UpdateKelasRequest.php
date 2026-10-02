<?php

namespace App\Http\Requests;

use App\Models\Guru;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKelasRequest extends FormRequest
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
            'tingkat' => ['required', 'string', 'max:255'],
            'nama' => ['required', 'string', 'max:255'],
            'wali_kelas_id' => ['nullable', 'integer', Rule::exists(Guru::class, 'id')],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tingkat.required' => 'Tingkat kelas wajib diisi.',
            'nama.required' => 'Nama kelas wajib diisi.',
            'wali_kelas_id.integer' => 'Wali kelas yang dipilih tidak valid.',
            'wali_kelas_id.exists' => 'Wali kelas yang dipilih tidak valid.',
        ];
    }
}
