<?php

namespace App\Http\Requests;

use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\TujuanPembelajaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenilaianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user?->role !== 'guru' || ! $this->filled('pengampu_id')) {
            return $user?->role === 'guru' && $user->guru()->exists();
        }

        $assignment = Pengampu::query()->find($this->integer('pengampu_id'));

        return $assignment !== null && $user->can('view', $assignment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pengampu_id' => ['required', 'integer', Rule::exists(Pengampu::class, 'id')],
            'tujuan_pembelajaran_id' => ['required', 'integer', Rule::exists(TujuanPembelajaran::class, 'id')],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['tugas', 'ulangan_harian', 'uts', 'uas'])],
            'kategori_asesmen' => ['required', Rule::in(array_keys(Penilaian::KATEGORI_LABELS))],
            'tanggal' => ['required', 'date'],
            'bobot' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'urutan' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $goalMatchesAssignment = TujuanPembelajaran::query()
                ->whereKey($this->integer('tujuan_pembelajaran_id'))
                ->where('pengampu_id', $this->integer('pengampu_id'))
                ->exists();

            if (! $goalMatchesAssignment) {
                $validator->errors()->add('tujuan_pembelajaran_id', 'Tujuan pembelajaran harus berasal dari tugas mengajar yang dipilih.');

                return;
            }

            $duplicateExists = Penilaian::query()
                ->where('pengampu_id', $this->integer('pengampu_id'))
                ->where('nama', $this->string('nama')->trim()->toString())
                ->where('jenis', $this->string('jenis')->toString())
                ->whereDate('tanggal', $this->date('tanggal'))
                ->where('urutan', $this->integer('urutan'))
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('nama', 'Penilaian dengan data yang sama sudah tersedia.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pengampu_id.required' => 'Tugas mengajar wajib dipilih.',
            'pengampu_id.exists' => 'Tugas mengajar yang dipilih tidak tersedia.',
            'tujuan_pembelajaran_id.required' => 'Tujuan pembelajaran wajib dipilih.',
            'tujuan_pembelajaran_id.exists' => 'Tujuan pembelajaran yang dipilih tidak tersedia.',
            'nama.required' => 'Nama penilaian wajib diisi.',
            'nama.max' => 'Nama penilaian maksimal 255 karakter.',
            'jenis.required' => 'Jenis penilaian wajib dipilih.',
            'jenis.in' => 'Jenis penilaian tidak valid.',
            'kategori_asesmen.required' => 'Kategori pengolahan asesmen wajib dipilih.',
            'kategori_asesmen.in' => 'Kategori pengolahan asesmen tidak valid.',
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
