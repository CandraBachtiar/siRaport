<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tahun_ajaran') || ! Schema::hasColumn('tahun_ajaran', 'tahun') || ! Schema::hasColumn('tahun_ajaran', 'semester')) {
            throw new RuntimeException('Migrasi tahun ajaran dihentikan: tabel atau kolom tahun dan semester tidak lengkap.');
        }

        $duplicate = DB::table('tahun_ajaran')
            ->select(['tahun', 'semester'])
            ->groupBy(['tahun', 'semester'])
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(
                "Migrasi tahun ajaran dihentikan: kombinasi {$duplicate->tahun} dan {$duplicate->semester} sudah memiliki data duplikat. Rapikan data tersebut secara manual sebelum constraint unik ditambahkan."
            );
        }

        $existingIndex = collect(Schema::getIndexes('tahun_ajaran'))
            ->first(fn (array $index): bool => $index['name'] === 'tahun_ajaran_tahun_semester_unique');

        if ($existingIndex !== null && (! $existingIndex['unique'] || $existingIndex['columns'] !== ['tahun', 'semester'])) {
            throw new RuntimeException('Migrasi tahun ajaran dihentikan: nama index tahun_ajaran_tahun_semester_unique sudah digunakan untuk struktur yang berbeda.');
        }

        if (! $this->hasUniqueCombinationIndex()) {
            Schema::table('tahun_ajaran', function (Blueprint $table): void {
                $table->unique(['tahun', 'semester'], 'tahun_ajaran_tahun_semester_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('tahun_ajaran_tahun_semester_unique')) {
            Schema::table('tahun_ajaran', function (Blueprint $table): void {
                $table->dropUnique('tahun_ajaran_tahun_semester_unique');
            });
        }
    }

    private function hasUniqueCombinationIndex(): bool
    {
        return collect(Schema::getIndexes('tahun_ajaran'))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['tahun', 'semester']
        );
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('tahun_ajaran'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
