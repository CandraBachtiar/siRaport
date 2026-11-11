<?php

namespace App\Http\Requests;

use App\Models\Penilaian;
use App\Models\TujuanPembelajaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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
            'tujuan_pembelajaran_id' => ['sometimes', 'nullable', 'integer', Rule::exists(TujuanPembelajaran::class, 'id')],
            'jenis' => ['required', Rule::in(['tugas', 'ulangan_harian', 'uts', 'uas'])],
            'kategori_asesmen' => ['sometimes', 'nullable', Rule::in(array_keys(Penilaian::KATEGORI_LABELS))],
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

            $assessment = $this->route('penilaian');

            if (! $assessment instanceof Penilaian) {
                return;
            }

            if ($this->exists('kategori_asesmen')) {
                $category = $this->input('kategori_asesmen');

                if ($category === null && $assessment->kategori_asesmen !== null) {
                    $validator->errors()->add('kategori_asesmen', 'Kategori asesmen yang sudah ditetapkan tidak boleh dikosongkan.');

                    return;
                }

                if ($category !== null
                    && $assessment->kategori_asesmen !== null
                    && $category !== $assessment->kategori_asesmen
                    && $assessment->nilai()->exists()) {
                    $validator->errors()->add('kategori_asesmen', 'Kategori tidak dapat diubah setelah nilai siswa tersimpan.');

                    return;
                }
            }

            if ($this->exists('tujuan_pembelajaran_id')) {
                $tujuanPembelajaranId = $this->input('tujuan_pembelajaran_id');

                if ($tujuanPembelajaranId === null && $assessment->tujuan_pembelajaran_id !== null) {
                    $validator->errors()->add('tujuan_pembelajaran_id', 'TP yang sudah dipilih tidak boleh dikosongkan.');

                    return;
                }

                if ($tujuanPembelajaranId !== null && ! TujuanPembelajaran::query()
                    ->whereKey($tujuanPembelajaranId)
                    ->where('pengampu_id', $assessment->pengampu_id)
                    ->exists()) {
                    $validator->errors()->add('tujuan_pembelajaran_id', 'Tujuan pembelajaran harus berasal dari tugas mengajar penilaian ini.');

                    return;
                }

                if ($tujuanPembelajaranId !== null
                    && $assessment->tujuan_pembelajaran_id !== null
                    && (int) $tujuanPembelajaranId !== (int) $assessment->tujuan_pembelajaran_id
                    && $assessment->nilai()->exists()) {
                    $validator->errors()->add('tujuan_pembelajaran_id', 'TP penilaian tidak dapat diubah setelah nilai siswa tersimpan.');

                    return;
                }
            }

            $duplicateExists = Penilaian::query()
                ->where('pengampu_id', $assessment->pengampu_id)
                ->whereKeyNot($assessment->id)
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
            'nama.required' => 'Nama penilaian wajib diisi.',
            'tujuan_pembelajaran_id.exists' => 'Tujuan pembelajaran yang dipilih tidak tersedia.',
            'nama.max' => 'Nama penilaian maksimal 255 karakter.',
            'jenis.required' => 'Jenis penilaian wajib dipilih.',
            'jenis.in' => 'Jenis penilaian tidak valid.',
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
