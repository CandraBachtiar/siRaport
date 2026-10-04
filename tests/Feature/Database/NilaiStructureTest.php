<?php

namespace Tests\Feature\Database;

use App\Models\Nilai;
use App\Models\Pengampu;
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

    public function test_nilai_has_siswa_and_pengampu_relationships(): void
    {
        $siswa = Siswa::factory()->create();
        $pengampu = Pengampu::factory()->create();
        $nilai = Nilai::create(['siswa_id' => $siswa->id, 'pengampu_id' => $pengampu->id]);

        $this->assertSame($siswa->id, $nilai->siswa->id);
        $this->assertSame($pengampu->id, $nilai->pengampu->id);
        $this->assertTrue($siswa->nilai->contains($nilai));
        $this->assertTrue($pengampu->nilai->contains($nilai));
    }

    public function test_siswa_and_pengampu_combination_is_unique(): void
    {
        $siswa = Siswa::factory()->create();
        $pengampu = Pengampu::factory()->create();
        Nilai::create(['siswa_id' => $siswa->id, 'pengampu_id' => $pengampu->id]);

        $this->expectException(QueryException::class);
        Nilai::create(['siswa_id' => $siswa->id, 'pengampu_id' => $pengampu->id]);
    }

    public function test_nilai_schema_contains_target_columns_foreign_keys_and_unique_index(): void
    {
        $columns = collect(Schema::getColumns('nilai'))->pluck('name')->all();
        $indexes = collect(Schema::getIndexes('nilai'));

        $this->assertSame([
            'id', 'siswa_id', 'pengampu_id', 'tugas', 'ulangan_harian', 'uts', 'uas', 'nilai_akhir', 'created_at', 'updated_at',
        ], $columns);
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['name'] === 'nilai_siswa_id_pengampu_id_unique' && $index['unique'] && $index['columns'] === ['siswa_id', 'pengampu_id']));
    }
}
