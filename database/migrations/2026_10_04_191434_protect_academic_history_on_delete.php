<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{table: string, column: string, referencedTable: string, name: string}> */
    private array $academicRelations = [
        ['table' => 'pengampu', 'column' => 'guru_id', 'referencedTable' => 'guru', 'name' => 'pengampu_guru_id_foreign'],
        ['table' => 'pengampu', 'column' => 'mata_pelajaran_id', 'referencedTable' => 'mata_pelajaran', 'name' => 'pengampu_mata_pelajaran_id_foreign'],
        ['table' => 'pengampu', 'column' => 'kelas_id', 'referencedTable' => 'kelas', 'name' => 'pengampu_kelas_id_foreign'],
        ['table' => 'pengampu', 'column' => 'tahun_ajaran_id', 'referencedTable' => 'tahun_ajaran', 'name' => 'pengampu_tahun_ajaran_id_foreign'],
        ['table' => 'siswa', 'column' => 'kelas_id', 'referencedTable' => 'kelas', 'name' => 'siswa_kelas_id_foreign'],
        ['table' => 'penilaian', 'column' => 'pengampu_id', 'referencedTable' => 'pengampu', 'name' => 'penilaian_pengampu_id_foreign'],
        ['table' => 'nilai', 'column' => 'siswa_id', 'referencedTable' => 'siswa', 'name' => 'nilai_siswa_id_foreign'],
        ['table' => 'nilai', 'column' => 'penilaian_id', 'referencedTable' => 'penilaian', 'name' => 'nilai_penilaian_id_foreign'],
        ['table' => 'deskripsi', 'column' => 'nilai_id', 'referencedTable' => 'nilai', 'name' => 'deskripsi_nilai_id_foreign'],
    ];

    public function up(): void
    {
        $this->replaceAcademicForeignKeys('restrict');
    }

    public function down(): void
    {
        $this->replaceAcademicForeignKeys('cascade');
    }

    private function replaceAcademicForeignKeys(string $deleteAction): void
    {
        foreach ($this->academicRelations as $relation) {
            $existing = collect(Schema::getForeignKeys($relation['table']))->first(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$relation['column']]
                    && $foreignKey['foreign_table'] === $relation['referencedTable']
            );

            if ($existing !== null) {
                Schema::table($relation['table'], function (Blueprint $table) use ($existing): void {
                    $table->dropForeign($existing['name']);
                });
            }

            Schema::table($relation['table'], function (Blueprint $table) use ($relation, $deleteAction): void {
                $foreign = $table->foreign(
                    $relation['column'],
                    $relation['name']
                )->references('id')->on($relation['referencedTable']);

                if ($deleteAction === 'restrict') {
                    $foreign->restrictOnDelete();
                } else {
                    $foreign->cascadeOnDelete();
                }
            });
        }
    }
};
