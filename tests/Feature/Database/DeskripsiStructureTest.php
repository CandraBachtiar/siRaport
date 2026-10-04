<?php

namespace Tests\Feature\Database;

use App\Models\Deskripsi;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class DeskripsiStructureTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_deskripsi_has_one_nilai_and_can_traverse_academic_context(): void
    {
        $siswa = Siswa::factory()->create();
        $pengampu = Pengampu::factory()->create();
        $penilaian = Penilaian::factory()->create(['pengampu_id' => $pengampu->id]);
        $nilai = Nilai::factory()->create([
            'siswa_id' => $siswa->id,
            'penilaian_id' => $penilaian->id,
        ]);
        $deskripsi = Deskripsi::create([
            'nilai_id' => $nilai->id,
            'rekomendasi' => 'Menunjukkan pemahaman yang baik.',
            'status' => 'draft',
        ]);

        $this->assertSame($nilai->id, $deskripsi->nilai->id);
        $this->assertSame($deskripsi->id, $nilai->deskripsi->id);
        $this->assertSame($siswa->id, $deskripsi->nilai->siswa->id);
        $this->assertSame(
            $pengampu->mata_pelajaran_id,
            $deskripsi->nilai->penilaian->pengampu->mataPelajaran->id
        );
        $this->assertSame(
            $pengampu->tahun_ajaran_id,
            $deskripsi->nilai->penilaian->pengampu->tahunAjaran->id
        );
        $this->assertSame('draft', $deskripsi->status);
    }

    public function test_one_nilai_cannot_have_multiple_descriptions(): void
    {
        $nilai = Nilai::factory()->create();
        Deskripsi::factory()->create(['nilai_id' => $nilai->id]);

        $this->expectException(QueryException::class);
        Deskripsi::factory()->create(['nilai_id' => $nilai->id]);
    }

    public function test_deskripsi_rejects_unknown_nilai_foreign_key(): void
    {
        $this->expectException(QueryException::class);
        Deskripsi::create(['nilai_id' => 999999, 'status' => 'draft']);
    }

    public function test_database_restricts_deleting_nilai_with_deskripsi(): void
    {
        $nilai = Nilai::factory()->create();
        Deskripsi::factory()->create(['nilai_id' => $nilai->id]);

        $this->expectException(QueryException::class);
        $nilai->delete();
    }

    public function test_database_restricts_deleting_pengampu_with_penilaian(): void
    {
        $pengampu = Pengampu::factory()->create();
        Penilaian::factory()->create(['pengampu_id' => $pengampu->id]);

        $this->expectException(QueryException::class);
        $pengampu->delete();
    }

    public function test_deskripsi_status_is_limited_to_draft_or_tervalidasi(): void
    {
        $nilai = Nilai::factory()->create();

        $this->expectException(QueryException::class);
        Deskripsi::create(['nilai_id' => $nilai->id, 'status' => 'disetujui']);
    }

    public function test_deskripsi_schema_has_no_redundant_context_columns(): void
    {
        $columns = collect(Schema::getColumns('deskripsi'))->pluck('name')->all();
        $indexes = collect(Schema::getIndexes('deskripsi'));

        $this->assertSame([
            'id', 'nilai_id', 'rekomendasi', 'deskripsi_akhir', 'status', 'created_at', 'updated_at',
        ], $columns);
        $this->assertFalse(Schema::hasColumn('deskripsi', 'siswa_id'));
        $this->assertFalse(Schema::hasColumn('deskripsi', 'mata_pelajaran_id'));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['name'] === 'deskripsi_nilai_id_unique'
                && $index['unique']
                && $index['columns'] === ['nilai_id']
        ));
    }
}
