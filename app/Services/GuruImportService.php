<?php

namespace App\Services;

use App\Imports\GuruRowsImport;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class GuruImportService
{
    /**
     * @return array{headerError: ?string, rows: array<int, array<string, mixed>>}
     */
    public function preview(UploadedFile $file): array
    {
        $import = new GuruRowsImport;
        $sheets = Excel::toArray($import, $file);
        $rows = $sheets[0] ?? [];

        if ($rows === []) {
            return ['headerError' => 'File Excel tidak berisi data.', 'rows' => []];
        }

        $headers = array_map(
            fn (mixed $header): string => Str::of((string) $header)->trim()->lower()->replace(' ', '_')->toString(),
            $rows[0],
        );

        if ($headers !== ['nip', 'nama_guru', 'email']) {
            return ['headerError' => 'Format kolom Excel tidak sesuai dengan template.', 'rows' => []];
        }

        $dataRows = array_slice($rows, 1);
        $existingNips = Guru::query()
            ->whereIn('nip', collect($dataRows)->pluck(0)->filter()->map(fn (mixed $value): string => trim((string) $value))->all())
            ->pluck('nip')
            ->flip();
        $existingEmails = User::query()
            ->whereIn('email', collect($dataRows)->pluck(2)->filter()->map(fn (mixed $value): string => trim((string) $value))->all())
            ->pluck('email')
            ->mapWithKeys(fn (string $email): array => [strtolower($email) => true]);
        $seenNips = [];
        $seenEmails = [];
        $previewRows = [];

        foreach ($dataRows as $index => $row) {
            $nip = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $email = trim((string) ($row[2] ?? ''));
            $errors = Validator::make(
                ['name' => $name, 'nip' => $nip, 'email' => $email],
                [
                    'name' => ['required', 'string', 'max:255'],
                    'nip' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                ],
                [
                    'name.required' => 'Nama guru wajib diisi.',
                    'nip.required' => 'NIP wajib diisi.',
                    'email.required' => 'Email wajib diisi.',
                    'email.email' => 'Format email tidak valid.',
                ],
            )->errors()->all();

            $emailKey = strtolower($email);
            if ($nip !== '' && isset($existingNips[$nip])) {
                $errors[] = 'NIP sudah terdaftar.';
            }
            if ($nip !== '' && isset($seenNips[$nip])) {
                $errors[] = 'NIP duplikat pada file.';
            }
            if ($emailKey !== '' && isset($existingEmails[$emailKey])) {
                $errors[] = 'Email sudah digunakan.';
            }
            if ($emailKey !== '' && isset($seenEmails[$emailKey])) {
                $errors[] = 'Email duplikat pada file.';
            }

            if ($nip !== '') {
                $seenNips[$nip] = true;
            }
            if ($emailKey !== '') {
                $seenEmails[$emailKey] = true;
            }

            $previewRows[] = [
                'row' => $index + 2,
                'nip' => $nip,
                'name' => $name,
                'email' => $email,
                'valid' => $errors === [],
                'message' => implode(' ', array_unique($errors)),
            ];
        }

        if ($previewRows === []) {
            return ['headerError' => 'File Excel tidak berisi data.', 'rows' => []];
        }

        return ['headerError' => null, 'rows' => $previewRows];
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function import(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            $count = 0;

            foreach ($rows as $row) {
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'guru',
                ]);

                $user->guru()->create([
                    'nip' => $row['nip'],
                    'nama' => $row['name'],
                ]);
                $count++;
            }

            return $count;
        });
    }
}
