<?php

namespace App\Services\Taxonomy;

use App\Models\PostCategory;
use App\Models\PostSubcategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class TaxonomyService
{
    public function categories(): Collection
    {
        return PostCategory::query()
            ->where('is_active', true)
            ->with([
                'subcategories' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function subcategories(PostCategory $category): Collection
    {
        if (! $category->is_active) {
            throw (new ModelNotFoundException())->setModel(PostCategory::class, [$category->id]);
        }

        return PostSubcategory::query()
            ->where('post_category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
