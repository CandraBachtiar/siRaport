<?php

namespace App\Http\Requests;

use App\Models\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveDataRaporSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('siswa');
        $guru = $this->user()?->guru;

        return $student instanceof Siswa && $guru !== null
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
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajaran,id'],
            'sakit' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'izin' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'tanpa_keterangan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'catatan_wali_kelas' => ['nullable', 'string', 'max:3000'],
            'ekstrakurikuler' => ['nullable', 'array', 'max:20'],
            'ekstrakurikuler.*.nama_kegiatan' => ['nullable', 'string', 'max:150'],
            'ekstrakurikuler.*.keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $activities = $this->input('ekstrakurikuler', []);

            if (! is_array($activities)) {
                return;
            }

            foreach ($activities as $index => $activity) {
                if (! is_array($activity)) {
                    continue;
                }

                $name = trim((string) ($activity['nama_kegiatan'] ?? ''));
                $description = trim((string) ($activity['keterangan'] ?? ''));

                if ($name === '' && $description !== '') {
                    $validator->errors()->add("ekstrakurikuler.{$index}.nama_kegiatan", 'Nama kegiatan wajib diisi jika keterangan dicantumkan.');
                }
            }
        }];
    }
}
