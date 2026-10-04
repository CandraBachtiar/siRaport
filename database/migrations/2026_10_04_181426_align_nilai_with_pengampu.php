<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyColumns = ['guru_id', 'mata_pelajaran_id', 'tahun_ajaran_id'];

        if (! Schema::hasColumn('nilai', 'pengampu_id')) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->unsignedBigInteger('pengampu_id')->nullable()->after('siswa_id');
            });
        }

        $legacyColumnCount = collect($legacyColumns)->filter(fn (string $column): bool => Schema::hasColumn('nilai', $column))->count();

        if ($legacyColumnCount > 0 && $legacyColumnCount < count($legacyColumns)) {
            throw new RuntimeException('Migrasi nilai dihentikan: hanya sebagian kolom relasi lama yang ditemukan. Struktur nilai harus diperiksa dan dipetakan secara manual.');
        }

        if ($this->hasLegacyColumns($legacyColumns)) {
            $this->mapLegacyValues();
        }

        if (DB::table('nilai')->whereNull('pengampu_id')->exists()) {
            throw new RuntimeException('Migrasi nilai dihentikan: ada data nilai yang tidak dapat dipetakan ke pengampu. Periksa kombinasi siswa, guru, mata pelajaran, dan tahun ajaran terlebih dahulu.');
        }

        Schema::table('nilai', function (Blueprint $table): void {
            $table->unsignedBigInteger('pengampu_id')->nullable(false)->change();
        });

        if ($this->hasLegacyColumns($legacyColumns)) {
            $this->dropLegacyColumns($legacyColumns);
        }

        $this->addPengampuForeignKey();
        $this->addUniqueConstraint();
    }

    public function down(): void
    {
        if ($this->hasIndex(['siswa_id', 'pengampu_id'], 'nilai_siswa_id_pengampu_id_unique')) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->dropUnique('nilai_siswa_id_pengampu_id_unique');
            });
        }
    }

    /** @param array<int, string> $columns */
    private function hasLegacyColumns(array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn('nilai', $column)) {
                return false;
            }
        }

        return true;
    }

    private function mapLegacyValues(): void
    {
        DB::table('nilai')
            ->whereNull('pengampu_id')
            ->orderBy('id')
            ->eachById(function (object $nilai): void {
                $matches = DB::table('pengampu')
                    ->join('siswa', 'siswa.kelas_id', '=', 'pengampu.kelas_id')
                    ->where('siswa.id', $nilai->siswa_id)
                    ->where('pengampu.guru_id', $nilai->guru_id)
                    ->where('pengampu.mata_pelajaran_id', $nilai->mata_pelajaran_id)
                    ->where('pengampu.tahun_ajaran_id', $nilai->tahun_ajaran_id)
                    ->select('pengampu.id')
                    ->get();

                if ($matches->count() !== 1) {
                    throw new RuntimeException("Migrasi nilai dihentikan: nilai ID {$nilai->id} memiliki {$matches->count()} kandidat pengampu.");
                }

                DB::table('nilai')->where('id', $nilai->id)->update(['pengampu_id' => $matches->first()->id]);
            });
    }

    /** @param array<int, string> $columns */
    private function dropLegacyColumns(array $columns): void
    {
        foreach (Schema::getForeignKeys('nilai') as $foreignKey) {
            if (count(array_intersect($foreignKey['columns'], $columns)) > 0) {
                Schema::table('nilai', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }

        foreach (Schema::getIndexes('nilai') as $index) {
            if ($index['unique'] && $index['columns'] === ['siswa_id', 'mata_pelajaran_id', 'guru_id', 'tahun_ajaran_id']) {
                Schema::table('nilai', function (Blueprint $table) use ($index): void {
                    $table->dropUnique($index['name']);
                });
            }
        }

        Schema::table('nilai', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    private function addPengampuForeignKey(): void
    {
        $hasForeignKey = collect(Schema::getForeignKeys('nilai'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['pengampu_id'] && $foreignKey['foreign_table'] === 'pengampu');

        if (! $hasForeignKey) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->foreign('pengampu_id', 'nilai_pengampu_id_foreign')
                    ->references('id')
                    ->on('pengampu')
                    ->cascadeOnDelete();
            });
        }
    }

    private function addUniqueConstraint(): void
    {
        if ($this->hasIndex(['siswa_id', 'pengampu_id'], 'nilai_siswa_id_pengampu_id_unique')) {
            return;
        }

        if (DB::table('nilai')
            ->select(['siswa_id', 'pengampu_id'])
            ->groupBy(['siswa_id', 'pengampu_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Migrasi nilai dihentikan: terdapat duplikat kombinasi siswa_id dan pengampu_id.');
        }

        Schema::table('nilai', function (Blueprint $table): void {
            $table->unique(['siswa_id', 'pengampu_id'], 'nilai_siswa_id_pengampu_id_unique');
        });
    }

    /** @param array<int, string> $columns */
    private function hasIndex(array $columns, string $name): bool
    {
        return collect(Schema::getIndexes('nilai'))
            ->contains(fn (array $index): bool => $index['name'] === $name && $index['columns'] === $columns);
    }
};
