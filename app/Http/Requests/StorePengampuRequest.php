<?php

namespace App\Http\Requests;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengampuRequest extends FormRequest
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
            'guru_id' => [
                'required', 'integer', Rule::exists(Guru::class, 'id'),
                Rule::unique(Pengampu::class, 'guru_id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('guru_id', $this->input('guru_id'))
                        ->where('mata_pelajaran_id', $this->input('mata_pelajaran_id'))
                        ->where('kelas_id', $this->input('kelas_id'))
                        ->where('tahun_ajaran_id', $this->input('tahun_ajaran_id'))),
            ],
            'mata_pelajaran_id' => ['required', 'integer', Rule::exists(MataPelajaran::class, 'id')],
            'kelas_id' => ['required', 'integer', Rule::exists(Kelas::class, 'id')],
            'tahun_ajaran_id' => [
                'required', 'integer', Rule::exists(TahunAjaran::class, 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Semua data pengampu wajib dipilih.',
            'guru_id.exists' => 'Guru yang dipilih tidak tersedia.',
            'mata_pelajaran_id.exists' => 'Mata pelajaran yang dipilih tidak tersedia.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak tersedia.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran yang dipilih tidak tersedia.',
            'guru_id.unique' => 'Kombinasi guru, mata pelajaran, kelas, dan tahun ajaran sudah digunakan.',
        ];
    }
}
