<?php

namespace App\Http\Requests;

use App\Models\MataPelajaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMataPelajaranRequest extends FormRequest
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
        /** @var MataPelajaran $mataPelajaran */
        $mataPelajaran = $this->route('mata_pelajaran');

        return [
            'kode' => ['required', 'string', 'max:255', Rule::unique(MataPelajaran::class, 'kode')->ignore($mataPelajaran)],
            'nama' => ['required', 'string', 'max:255'],
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
            'kode.required' => 'Kode mata pelajaran wajib diisi.',
            'kode.unique' => 'Kode mata pelajaran sudah digunakan.',
            'nama.required' => 'Nama mata pelajaran wajib diisi.',
        ];
    }
}
