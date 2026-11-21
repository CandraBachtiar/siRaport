<?php

namespace App\Http\Requests;

use App\Enums\AssessmentPlacementStatus;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Services\AssessmentPlacementChecker;
use App\Services\AssessmentRosterService;
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

            $studentKeys = array_keys($submittedScores);

            foreach ($studentKeys as $studentId) {
                if (! is_string($studentId) && ! is_int($studentId) || ! preg_match('/^[1-9][0-9]*$/', (string) $studentId)) {
                    $validator->errors()->add('nilai.'.$studentId, 'ID siswa tidak valid.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $actor = $this->user();

            if ($actor === null) {
                return;
            }

            $candidateIds = app(AssessmentRosterService::class)->studentIds($actor, $assessment);
            $statuses = app(AssessmentPlacementChecker::class)->check($actor, $assessment->pengampu, $assessment, $candidateIds);
            $currentClassStudentIds = Siswa::query()
                ->where('kelas_id', $assessment->pengampu->kelas_id)
                ->pluck('id')
                ->all();

            foreach ($studentKeys as $studentId) {
                $numericStudentId = (int) $studentId;
                $status = $statuses->get($numericStudentId);
                $isLegacyCurrentStudent = in_array($numericStudentId, $currentClassStudentIds, true)
                    && in_array($status, [AssessmentPlacementStatus::Unknown, AssessmentPlacementStatus::DateUnavailable], true);

                if ($status === AssessmentPlacementStatus::MatchesAssignment || $isLegacyCurrentStudent) {
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
