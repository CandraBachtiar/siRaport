<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $legacyScoreColumns = [
        'tugas',
        'ulangan_harian',
        'uts',
        'uas',
        'nilai_akhir',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('nilai') || ! Schema::hasColumn('nilai', 'siswa_id')) {
            throw new RuntimeException('Migrasi nilai dihentikan: tabel nilai atau kolom siswa_id tidak ditemukan.');
        }

        if (! Schema::hasTable('penilaian')) {
            throw new RuntimeException('Migrasi nilai dihentikan: tabel penilaian belum tersedia. Jalankan migration create_penilaian_table terlebih dahulu.');
        }

        $presentLegacyColumns = collect($this->legacyScoreColumns)
            ->filter(fn (string $column): bool => Schema::hasColumn('nilai', $column))
            ->values();

        if ($presentLegacyColumns->isNotEmpty() && $presentLegacyColumns->count() !== count($this->legacyScoreColumns)) {
            throw new RuntimeException('Migrasi nilai dihentikan: hanya sebagian kolom nilai lama ditemukan. Struktur nilai harus diperiksa dan dipetakan secara manual.');
        }

        $hasLegacyStructure = Schema::hasColumn('nilai', 'pengampu_id') || $presentLegacyColumns->isNotEmpty();

        if ($hasLegacyStructure && DB::table('nilai')->exists()) {
            throw new RuntimeException(
                'Migrasi nilai dihentikan: data nilai lama masih ada dan tidak dapat diubah menjadi riwayat penilaian tanpa mengarang nama, tanggal, atau urutan penilaian. Arsipkan atau migrasikan data secara manual terlebih dahulu.'
            );
        }

        if (Schema::hasColumn('nilai', 'penilaian_id') && DB::table('nilai')->whereNull('penilaian_id')->exists()) {
            throw new RuntimeException('Migrasi nilai dihentikan: terdapat nilai tanpa penilaian_id yang tidak dapat dipetakan secara aman.');
        }

        if (Schema::hasColumn('nilai', 'penilaian_id') && DB::table('nilai')
            ->select(['siswa_id', 'penilaian_id'])
            ->groupBy(['siswa_id', 'penilaian_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Migrasi nilai dihentikan: terdapat duplikat kombinasi siswa_id dan penilaian_id.');
        }

        Schema::table('nilai', function (Blueprint $table): void {
            if (! Schema::hasColumn('nilai', 'penilaian_id')) {
                $table->foreignId('penilaian_id')->nullable()->after('siswa_id');
            }

            if (! Schema::hasColumn('nilai', 'nilai')) {
                $table->decimal('nilai', 5, 2)->nullable()->after('penilaian_id');
            }
        });

        $this->dropForeignKeysForColumn('pengampu_id');
        $this->dropUniqueIndexesContainingColumn('pengampu_id');

        $columnsToDrop = collect([...$this->legacyScoreColumns, 'pengampu_id'])
            ->filter(fn (string $column): bool => Schema::hasColumn('nilai', $column))
            ->values()
            ->all();

        if ($columnsToDrop !== []) {
            Schema::table('nilai', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::table('nilai', function (Blueprint $table): void {
            $table->unsignedBigInteger('penilaian_id')->nullable(false)->change();
            $table->decimal('nilai', 5, 2)->nullable(false)->change();
        });

        $this->ensureForeignKey('penilaian_id', 'penilaian', 'nilai_penilaian_id_foreign');

        if (! $this->hasUniqueIndex(['siswa_id', 'penilaian_id'])) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->unique(['siswa_id', 'penilaian_id'], 'nilai_siswa_id_penilaian_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (DB::table('nilai')->exists()) {
            throw new RuntimeException(
                'Rollback transformasi nilai dihentikan karena tabel nilai berisi data riwayat penilaian. Hapus atau arsipkan data tersebut terlebih dahulu.'
            );
        }

        if ($this->hasIndexNamed('nilai_siswa_id_penilaian_id_unique')) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->dropUnique('nilai_siswa_id_penilaian_id_unique');
            });
        }

        $this->dropForeignKeysForColumn('penilaian_id');

        $columnsToDrop = collect(['penilaian_id', 'nilai'])
            ->filter(fn (string $column): bool => Schema::hasColumn('nilai', $column))
            ->values()
            ->all();

        if ($columnsToDrop !== []) {
            Schema::table('nilai', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }

        $legacyScoreColumns = $this->legacyScoreColumns;

        Schema::table('nilai', function (Blueprint $table) use ($legacyScoreColumns): void {
            if (! Schema::hasColumn('nilai', 'pengampu_id')) {
                $table->foreignId('pengampu_id')->nullable()->after('siswa_id');
            }

            foreach ($legacyScoreColumns as $column) {
                if (! Schema::hasColumn('nilai', $column)) {
                    $table->decimal($column, 5, 2)->nullable();
                }
            }
        });

        Schema::table('nilai', function (Blueprint $table): void {
            $table->unsignedBigInteger('pengampu_id')->nullable(false)->change();
        });

        $this->ensureForeignKey('pengampu_id', 'pengampu', 'nilai_pengampu_id_foreign');

        if (! $this->hasUniqueIndex(['siswa_id', 'pengampu_id'])) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->unique(['siswa_id', 'pengampu_id'], 'nilai_siswa_id_pengampu_id_unique');
            });
        }
    }

    private function dropForeignKeysForColumn(string $column): void
    {
        foreach (Schema::getForeignKeys('nilai') as $foreignKey) {
            if ($foreignKey['columns'] !== [$column]) {
                continue;
            }

            Schema::table('nilai', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name']);
            });
        }
    }

    private function dropUniqueIndexesContainingColumn(string $column): void
    {
        foreach (Schema::getIndexes('nilai') as $index) {
            if (! $index['unique'] || ! in_array($column, $index['columns'], true)) {
                continue;
            }

            Schema::table('nilai', function (Blueprint $table) use ($index): void {
                $table->dropUnique($index['name']);
            });
        }
    }

    private function ensureForeignKey(string $column, string $referencedTable, string $name): void
    {
        foreach (Schema::getForeignKeys('nilai') as $foreignKey) {
            if ($foreignKey['columns'] !== [$column]) {
                continue;
            }

            Schema::table('nilai', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name']);
            });
        }

        Schema::table('nilai', function (Blueprint $table) use ($column, $referencedTable, $name): void {
            $table->foreign($column, $name)
                ->references('id')
                ->on($referencedTable)
                ->cascadeOnDelete();
        });
    }

    /** @param list<string> $columns */
    private function hasUniqueIndex(array $columns): bool
    {
        return collect(Schema::getIndexes('nilai'))
            ->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === $columns);
    }

    private function hasIndexNamed(string $name): bool
    {
        return collect(Schema::getIndexes('nilai'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
