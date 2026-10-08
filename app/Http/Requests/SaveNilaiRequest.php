<?php

namespace App\Http\Requests;

use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assessment = $this->route('penilaian');
            $submittedScores = $this->input('nilai');

            if (! $assessment instanceof Penilaian || ! is_array($submittedScores)) {
                return;
            }

            $assessment->loadMissing('pengampu:id,kelas_id');
            $allowedStudentIds = Siswa::query()
                ->where('kelas_id', $assessment->pengampu->kelas_id)
                ->whereIn('id', array_map('intval', array_keys($submittedScores)))
                ->pluck('id')
                ->all();

            foreach (array_keys($submittedScores) as $studentId) {
                if (in_array((int) $studentId, $allowedStudentIds, true)) {
                    continue;
                }

                $validator->errors()->add(
                    'nilai.'.$studentId,
                    'Siswa tidak termasuk dalam kelas pengampu penilaian ini.',
                );
            }
        });
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
