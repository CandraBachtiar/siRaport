<?php

namespace Tests\Feature\Database;

use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class NilaiStructureTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_nilai_has_siswa_and_penilaian_relationships(): void
    {
        $siswa = Siswa::factory()->create();
        $penilaian = Penilaian::factory()->create();
        $nilai = Nilai::create([
            'siswa_id' => $siswa->id,
            'penilaian_id' => $penilaian->id,
            'nilai' => 88,
        ]);

        $this->assertSame($siswa->id, $nilai->siswa->id);
        $this->assertSame($penilaian->id, $nilai->penilaian->id);
        $this->assertTrue($siswa->nilai->contains($nilai));
        $this->assertTrue($penilaian->nilai->contains($nilai));
        $this->assertTrue($penilaian->pengampu->penilaian->contains($penilaian));
    }

    public function test_siswa_and_penilaian_combination_is_unique(): void
    {
        $siswa = Siswa::factory()->create();
        $penilaian = Penilaian::factory()->create();
        Nilai::create(['siswa_id' => $siswa->id, 'penilaian_id' => $penilaian->id, 'nilai' => 80]);

        $this->expectException(QueryException::class);
        Nilai::create(['siswa_id' => $siswa->id, 'penilaian_id' => $penilaian->id, 'nilai' => 90]);
    }

    public function test_nilai_rejects_unknown_penilaian_foreign_key(): void
    {
        $siswa = Siswa::factory()->create();

        $this->expectException(QueryException::class);
        Nilai::create(['siswa_id' => $siswa->id, 'penilaian_id' => 999999, 'nilai' => 80]);
    }

    public function test_nilai_rejects_unknown_siswa_foreign_key(): void
    {
        $penilaian = Penilaian::factory()->create();

        $this->expectException(QueryException::class);
        Nilai::create(['siswa_id' => 999999, 'penilaian_id' => $penilaian->id, 'nilai' => 80]);
    }

    public function test_penilaian_can_be_ordered_by_date_then_sequence(): void
    {
        $pengampu = Pengampu::factory()->create();
        $later = Penilaian::factory()->create([
            'pengampu_id' => $pengampu->id,
            'tanggal' => '2026-01-02',
            'urutan' => 1,
        ]);
        $sameDateLater = Penilaian::factory()->create([
            'pengampu_id' => $pengampu->id,
            'tanggal' => '2026-01-01',
            'urutan' => 2,
        ]);
        $first = Penilaian::factory()->create([
            'pengampu_id' => $pengampu->id,
            'tanggal' => '2026-01-01',
            'urutan' => 1,
        ]);

        $this->assertSame(
            [$first->id, $sameDateLater->id, $later->id],
            Penilaian::query()
                ->where('pengampu_id', $pengampu->id)
                ->orderBy('tanggal')
                ->orderBy('urutan')
                ->pluck('id')
                ->all()
        );
    }

    public function test_nilai_schema_contains_only_chronological_structure(): void
    {
        $columns = collect(Schema::getColumns('nilai'))->pluck('name')->all();
        $indexes = collect(Schema::getIndexes('nilai'));

        $this->assertSame([
            'id', 'siswa_id', 'penilaian_id', 'nilai', 'created_at', 'updated_at',
        ], $columns);
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['name'] === 'nilai_siswa_id_penilaian_id_unique'
                && $index['unique']
                && $index['columns'] === ['siswa_id', 'penilaian_id']
        ));

        $foreignKeys = collect(Schema::getForeignKeys('nilai'));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['siswa_id']
                && $foreignKey['foreign_table'] === 'siswa'
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['penilaian_id']
                && $foreignKey['foreign_table'] === 'penilaian'
        ));
    }
}
