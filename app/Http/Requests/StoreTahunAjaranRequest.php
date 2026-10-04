<?php

namespace App\Http\Requests;

use App\Models\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTahunAjaranRequest extends FormRequest
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
            'tahun' => [
                'required', 'string', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique(TahunAjaran::class, 'tahun')->where(fn (Builder $query): Builder => $query->where('semester', $this->input('semester'))),
            ],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'aktif' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun ajaran wajib diisi.',
            'tahun.regex' => 'Format tahun ajaran harus seperti 2025/2026.',
            'tahun.unique' => 'Tahun ajaran dan semester tersebut sudah digunakan.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.in' => 'Semester harus Ganjil atau Genap.',
            'aktif.boolean' => 'Status aktif tidak valid.',
        ];
    }
}
