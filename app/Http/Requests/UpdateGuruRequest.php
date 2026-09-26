<?php

namespace App\Http\Requests;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateGuruRequest extends FormRequest
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
        /** @var Guru $guru */
        $guru = $this->route('guru');

        return [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:255', Rule::unique(Guru::class, 'nip')->ignore($guru)],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($guru->user_id)],
            'password' => ['nullable', Password::defaults()],
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
            'name.required' => 'Nama guru wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
        ];
    }
}
