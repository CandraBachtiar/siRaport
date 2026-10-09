<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /** @return array<string, array{string, string, string}> */
    public static function publicPages(): array
    {
        return [
            'landing page' => ['home', 'pages.home', 'Kelola nilai.'],
            'about page' => ['tentang', 'pages.tentang', 'Apa itu RaporKu?'],
            'guide page' => ['panduan', 'pages.panduan', 'Panduan Administrator'],
            'help page' => ['bantuan', 'pages.bantuan', 'Pertanyaan yang sering diajukan'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_page_renders_raporku_branding(string $routeName, string $viewName, string $expectedText): void
    {
        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertViewIs($viewName)
            ->assertSee('RaporKu')
            ->assertSee($expectedText)
            ->assertDontSee('EduRaport')
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false);
    }

    public function test_landing_page_explains_features_for_every_user_role(): void
    {
        $response = $this->get(route('home'));

        $response->assertSeeInOrder([
            'Untuk Administrator',
            'Untuk Guru Mapel',
            'Untuk Wali Kelas',
        ])->assertSee('Bagaimana RaporKu bekerja?');
    }

    public function test_public_navigation_keeps_primary_pages_without_feature_menu(): void
    {
        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['>Beranda</a>', '>Tentang</a>', '>Panduan</a>', '>Bantuan</a>'], false)
            ->assertDontSee('>Fitur</a>', false);
    }

    public function test_help_page_exposes_search_and_specific_problem_guidance(): void
    {
        $response = $this->get(route('bantuan'));

        $response->assertSee('Cari bantuan')
            ->assertSee('Mengapa tombol Cetak Rapor belum aktif?')
            ->assertSee('Hubungi administrator sekolah');
    }

    public function test_guide_tabs_expose_keyboard_accessible_relationships(): void
    {
        $response = $this->get(route('panduan'));

        $response->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('aria-selected:bg-raporku-navy', false)
            ->assertSee('sm:col-span-2 sm:mx-auto', false)
            ->assertSee('aria-labelledby="guide-tab-admin"', false)
            ->assertSee('aria-labelledby="guide-tab-guru"', false)
            ->assertSee('aria-labelledby="guide-tab-wali"', false);
    }
}
