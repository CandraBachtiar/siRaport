<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasSiswaColumn = Schema::hasColumn('deskripsi', 'siswa_id');
        $hasMataPelajaranColumn = Schema::hasColumn('deskripsi', 'mata_pelajaran_id');

        if ($hasSiswaColumn !== $hasMataPelajaranColumn) {
            throw new RuntimeException(
                'Migrasi deskripsi dihentikan: hanya sebagian relasi redundan ditemukan. Periksa struktur deskripsi secara manual.'
            );
        }

        if ($hasSiswaColumn) {
            $this->validateRedundantContext();
            $this->dropForeignKeysForColumns(['siswa_id', 'mata_pelajaran_id']);
        }

        if ($this->hasDuplicateNilaiRelations()) {
            throw new RuntimeException(
                'Migrasi deskripsi dihentikan: satu nilai memiliki lebih dari satu deskripsi. Data perlu digabungkan secara manual sebelum constraint satu-deskripsi-per-nilai ditambahkan.'
            );
        }

        $columnsToDrop = collect(['siswa_id', 'mata_pelajaran_id'])
            ->filter(fn (string $column): bool => Schema::hasColumn('deskripsi', $column))
            ->values()
            ->all();

        if ($columnsToDrop !== []) {
            Schema::table('deskripsi', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }

        if (! $this->hasIndex('deskripsi_nilai_id_unique')) {
            Schema::table('deskripsi', function (Blueprint $table): void {
                $table->unique('nilai_id', 'deskripsi_nilai_id_unique');
            });
        }
    }

    public function down(): void
    {
        $this->dropForeignKeysForColumns(['nilai_id']);

        if ($this->hasIndex('deskripsi_nilai_id_unique')) {
            Schema::table('deskripsi', function (Blueprint $table): void {
                $table->dropUnique('deskripsi_nilai_id_unique');
            });
        }

        Schema::table('deskripsi', function (Blueprint $table): void {
            if (! Schema::hasColumn('deskripsi', 'siswa_id')) {
                $table->foreignId('siswa_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('deskripsi', 'mata_pelajaran_id')) {
                $table->foreignId('mata_pelajaran_id')->nullable()->after('siswa_id');
            }
        });

        DB::table('deskripsi')
            ->orderBy('id')
            ->eachById(function (object $deskripsi): void {
                $context = DB::table('nilai as n')
                    ->join('penilaian as p', 'p.id', '=', 'n.penilaian_id')
                    ->join('pengampu as pg', 'pg.id', '=', 'p.pengampu_id')
                    ->where('n.id', $deskripsi->nilai_id)
                    ->select(['n.siswa_id', 'pg.mata_pelajaran_id'])
                    ->first();

                if ($context === null) {
                    throw new RuntimeException(
                        "Rollback deskripsi dihentikan: konteks akademik untuk deskripsi ID {$deskripsi->id} tidak dapat ditelusuri melalui nilai."
                    );
                }

                DB::table('deskripsi')->where('id', $deskripsi->id)->update([
                    'siswa_id' => $context->siswa_id,
                    'mata_pelajaran_id' => $context->mata_pelajaran_id,
                ]);
            });

        Schema::table('deskripsi', function (Blueprint $table): void {
            $table->unsignedBigInteger('siswa_id')->nullable(false)->change();
            $table->unsignedBigInteger('mata_pelajaran_id')->nullable(false)->change();
        });

        if (! $this->hasForeignKey('siswa_id', 'siswa')) {
            Schema::table('deskripsi', function (Blueprint $table): void {
                $table->foreign('siswa_id', 'deskripsi_siswa_id_foreign')
                    ->references('id')
                    ->on('siswa')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->hasForeignKey('mata_pelajaran_id', 'mata_pelajaran')) {
            Schema::table('deskripsi', function (Blueprint $table): void {
                $table->foreign('mata_pelajaran_id', 'deskripsi_mata_pelajaran_id_foreign')
                    ->references('id')
                    ->on('mata_pelajaran')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->hasForeignKey('nilai_id', 'nilai')) {
            Schema::table('deskripsi', function (Blueprint $table): void {
                $table->foreign('nilai_id', 'deskripsi_nilai_id_foreign')
                    ->references('id')
                    ->on('nilai')
                    ->cascadeOnDelete();
            });
        }
    }

    private function validateRedundantContext(): void
    {
        $invalid = DB::table('deskripsi as d')
            ->leftJoin('nilai as n', 'n.id', '=', 'd.nilai_id')
            ->leftJoin('penilaian as p', 'p.id', '=', 'n.penilaian_id')
            ->leftJoin('pengampu as pg', 'pg.id', '=', 'p.pengampu_id')
            ->where(function ($query): void {
                $query->whereNull('n.id')
                    ->orWhereColumn('d.siswa_id', '!=', 'n.siswa_id')
                    ->orWhereNull('pg.id')
                    ->orWhereColumn('d.mata_pelajaran_id', '!=', 'pg.mata_pelajaran_id');
            })
            ->select('d.id')
            ->first();

        if ($invalid !== null) {
            throw new RuntimeException(
                "Migrasi deskripsi dihentikan: relasi redundan pada deskripsi ID {$invalid->id} tidak konsisten dengan konteks nilai."
            );
        }
    }

    private function hasDuplicateNilaiRelations(): bool
    {
        return DB::table('deskripsi')
            ->select('nilai_id')
            ->groupBy('nilai_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    /** @param list<string> $columns */
    private function dropForeignKeysForColumns(array $columns): void
    {
        foreach (Schema::getForeignKeys('deskripsi') as $foreignKey) {
            if (count(array_intersect($foreignKey['columns'], $columns)) === 0) {
                continue;
            }

            Schema::table('deskripsi', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name']);
            });
        }
    }

    private function hasForeignKey(string $column, string $table): bool
    {
        return collect(Schema::getForeignKeys('deskripsi'))->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                && $foreignKey['foreign_table'] === $table
        );
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('deskripsi'))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
