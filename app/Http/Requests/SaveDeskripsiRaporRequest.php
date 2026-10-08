<?php

namespace App\Http\Requests;

use App\Models\Pengampu;
use App\Models\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDeskripsiRaporRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('siswa');
        $assignment = $this->route('pengampu');
        $guru = $this->user()?->guru;

        return $student instanceof Siswa
            && $assignment instanceof Pengampu
            && $guru !== null
            && $student->kelas_id === $assignment->kelas_id
            && $guru->kelasWali()->whereKey($student->kelas_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'deskripsi_akhir' => ['required', 'string', 'min:20', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'tervalidasi'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'deskripsi_akhir.required' => 'Deskripsi rapor wajib diisi.',
            'deskripsi_akhir.min' => 'Deskripsi rapor minimal 20 karakter agar informatif.',
            'deskripsi_akhir.max' => 'Deskripsi rapor maksimal 2.000 karakter.',
            'status.required' => 'Pilih status deskripsi.',
            'status.in' => 'Status deskripsi tidak valid.',
        ];
    }
}
