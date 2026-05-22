<?php

namespace Tests\Feature\Api;

use App\Models\PostCategory;
use App\Models\PostSubcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_active_categories_with_active_subcategories(): void
    {
        $category = PostCategory::query()->where('slug', 'community')->firstOrFail();

        PostCategory::query()->create([
            'name' => 'Hidden Category',
            'slug' => 'hidden-category',
            'description' => 'This should not be returned.',
            'is_active' => false,
            'sort_order' => 999,
        ]);

        PostSubcategory::query()->create([
            'post_category_id' => $category->id,
            'name' => 'Hidden Subcategory',
            'slug' => 'hidden-subcategory',
            'description' => 'This should not be returned.',
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $response = $this->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Categories fetched successfully.')
            ->assertJsonCount(PostCategory::query()->where('is_active', true)->count(), 'data')
            ->assertJsonMissing(['slug' => 'hidden-category'])
            ->assertJsonMissing(['slug' => 'hidden-subcategory']);

        $communityCategory = collect($response->json('data'))->firstWhere('slug', 'community');

        $this->assertNotNull($communityCategory);
        $this->assertContains('local-updates', array_column($communityCategory['subcategories'], 'slug'));
        $this->assertNotContains('hidden-subcategory', array_column($communityCategory['subcategories'], 'slug'));
    }

    public function test_subcategories_endpoint_returns_active_subcategories_for_a_category(): void
    {
        $category = PostCategory::query()->where('slug', 'general')->firstOrFail();

        PostSubcategory::query()->create([
            'post_category_id' => $category->id,
            'name' => 'Hidden Subcategory',
            'slug' => 'hidden-subcategory',
            'description' => 'This should not be returned.',
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $response = $this->getJson('/api/categories/'.$category->id.'/subcategories');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Subcategories fetched successfully.')
            ->assertJsonCount(
                PostSubcategory::query()
                    ->where('post_category_id', $category->id)
                    ->where('is_active', true)
                    ->count(),
                'data'
            )
            ->assertJsonMissing(['slug' => 'hidden-subcategory']);
    }

    public function test_subcategories_endpoint_returns_not_found_for_an_inactive_category(): void
    {
        $inactiveCategory = PostCategory::query()->create([
            'name' => 'Hidden Category',
            'slug' => 'hidden-category',
            'description' => 'This should not be returned.',
            'is_active' => false,
            'sort_order' => 999,
        ]);

        $this->getJson('/api/categories/'.$inactiveCategory->id.'/subcategories')
            ->assertNotFound();
    }
}
