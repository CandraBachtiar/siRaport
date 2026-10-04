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
        $hasLegacyStructure = Schema::hasColumn('nilai', 'pengampu_id')
            || collect($this->legacyScoreColumns)->contains(
                fn (string $column): bool => Schema::hasColumn('nilai', $column)
            );

        if ($hasLegacyStructure && DB::table('nilai')->exists()) {
            throw new RuntimeException(
                'Migrasi nilai dihentikan: data nilai lama masih ada dan tidak dapat diubah menjadi riwayat penilaian tanpa mengarang nama, tanggal, atau urutan penilaian. Arsipkan atau migrasikan data secara manual terlebih dahulu.'
            );
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

        if (! $this->hasForeignKeyForColumn('penilaian_id')) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->foreign('penilaian_id', 'nilai_penilaian_id_foreign')
                    ->references('id')
                    ->on('penilaian')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->hasIndex('nilai_siswa_id_penilaian_id_unique')) {
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

        if ($this->hasIndex('nilai_siswa_id_penilaian_id_unique')) {
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

        if (! $this->hasForeignKeyForColumn('pengampu_id')) {
            Schema::table('nilai', function (Blueprint $table): void {
                $table->foreign('pengampu_id', 'nilai_pengampu_id_foreign')
                    ->references('id')
                    ->on('pengampu')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->hasIndex('nilai_siswa_id_pengampu_id_unique')) {
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

    private function hasForeignKeyForColumn(string $column): bool
    {
        return collect(Schema::getForeignKeys('nilai'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]);
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('nilai'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
