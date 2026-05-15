<?php

namespace Tests\Feature\Admin;

use App\Models\NewsPost;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPostSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            GeographySeeder::class,
            AdminUserSeeder::class,
            DemoContentSeeder::class,
        ]);
    }

    public function test_duplicate_slug_gets_incremented(): void
    {
        $existingPost = NewsPost::query()->firstOrFail();
        $existingPost->update(['slug' => 'dada']);

        $this->assertSame('dada-2', NewsPost::ensureUniqueSlug('dada'));
    }

    public function test_current_record_slug_is_preserved_during_edit(): void
    {
        $post = NewsPost::query()->firstOrFail();
        $post->update(['slug' => 'kept-slug']);

        $this->assertSame('kept-slug', NewsPost::ensureUniqueSlug('kept slug', $post->id));
    }
}
