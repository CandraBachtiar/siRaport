<?php

namespace Tests\Feature\Database;

use App\Models\DataRaporSiswa;
use App\Models\EkstrakurikulerSiswa;
use App\Models\IdentitasSekolah;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RaporSupportMigrationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
        });
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('tahun');
            $table->string('semester');
        });
        Schema::create('deskripsi', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('draft');
        });
    }

    public function test_stage_six_migrations_create_nullable_fingerprint_period_data_and_relations_on_isolated_sqlite(): void
    {
        $fingerprintMigration = require base_path('database/migrations/2026_11_11_152609_add_validasi_fingerprint_to_deskripsi_table.php');
        $supportMigration = require base_path('database/migrations/2026_11_11_154521_create_rapor_support_tables.php');

        $fingerprintMigration->up();
        $supportMigration->up();

        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertTrue(Schema::hasColumn('deskripsi', 'validasi_fingerprint'));
        $fingerprintColumn = collect(Schema::getColumns('deskripsi'))->firstWhere('name', 'validasi_fingerprint');
        $this->assertSame('varchar', $fingerprintColumn['type_name']);
        $this->assertTrue($fingerprintColumn['nullable']);
        $this->assertTrue(Schema::hasTable('identitas_sekolah'));
        $this->assertTrue(Schema::hasTable('data_rapor_siswa'));
        $this->assertTrue(Schema::hasTable('ekstrakurikuler_siswa'));

        $legacyDescriptionId = DB::table('deskripsi')->insertGetId(['status' => 'tervalidasi']);
        $this->assertNull(DB::table('deskripsi')->where('id', $legacyDescriptionId)->value('validasi_fingerprint'));

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin']);
        DB::table('siswa')->insert(['id' => 1, 'nama' => 'Siswa']);
        DB::table('tahun_ajaran')->insert(['id' => 1, 'tahun' => '2025/2026', 'semester' => 'Genap']);
        $reportId = DataRaporSiswa::query()->create([
            'siswa_id' => 1,
            'tahun_ajaran_id' => 1,
            'keputusan_oleh' => 1,
        ])->id;
        $activityId = EkstrakurikulerSiswa::query()->create([
            'siswa_id' => 1,
            'tahun_ajaran_id' => 1,
            'nama_kegiatan' => 'Pramuka',
        ])->id;

        $report = DataRaporSiswa::query()->findOrFail($reportId);
        $activity = EkstrakurikulerSiswa::query()->findOrFail($activityId);
        $this->assertSame('Siswa', $report->siswa->nama);
        $this->assertSame('2025/2026', $report->tahunAjaran->tahun);
        $this->assertSame('Admin', $report->pengesah->name);
        $this->assertSame('Pramuka', $activity->nama_kegiatan);
        $this->assertSame(1, IdentitasSekolah::query()->create(['nama_sekolah' => 'SD Uji'])->id);

        $this->assertContains('data_rapor_siswa_periode_unique', collect(Schema::getIndexes('data_rapor_siswa'))->pluck('name'));
        $this->assertContains('ekstrakurikuler_siswa_periode_unique', collect(Schema::getIndexes('ekstrakurikuler_siswa'))->pluck('name'));
        $reportColumns = collect(Schema::getColumns('data_rapor_siswa'))->keyBy('name');
        $this->assertTrue($reportColumns['sakit']['nullable']);
        $this->assertTrue($reportColumns['izin']['nullable']);
        $this->assertTrue($reportColumns['tanpa_keterangan']['nullable']);
        $this->assertNotEmpty(Schema::getForeignKeys('data_rapor_siswa'));
        $this->assertNotEmpty(Schema::getForeignKeys('ekstrakurikuler_siswa'));

        $this->expectException(QueryException::class);
        DataRaporSiswa::query()->create(['siswa_id' => 99, 'tahun_ajaran_id' => 1]);
    }
}
