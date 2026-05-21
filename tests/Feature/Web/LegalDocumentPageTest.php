<?php

namespace Tests\Feature\Web;

use App\Models\LegalDocument;
use Database\Seeders\LegalDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDocumentPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LegalDocumentSeeder::class);
    }

    public function test_terms_and_privacy_pages_are_publicly_accessible(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms and Conditions')
            ->assertSee('Acceptance of Terms');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Information We Collect');
    }

    public function test_unpublished_legal_page_returns_not_found(): void
    {
        LegalDocument::query()
            ->where('slug', 'privacy-policy')
            ->update(['is_published' => false]);

        $this->get(route('legal.privacy'))
            ->assertNotFound();
    }
}
