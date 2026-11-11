<?php

namespace App\Http\Requests;

use App\Models\Penilaian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewNilaiImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('penilaian');
        $authorized = $assessment instanceof Penilaian && ($this->user()?->can('update', $assessment) ?? false);

        if ($authorized && $assessment instanceof Penilaian) {
            $this->session()->forget('guru.nilai_import_preview.'.$assessment->id);
        }

        return $authorized;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file Excel yang akan diperiksa.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
