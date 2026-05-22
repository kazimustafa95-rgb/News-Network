<?php

namespace App\Http\Controllers\Api\Taxonomy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PostCategoryResource;
use App\Http\Resources\Api\PostSubcategoryResource;
use App\Models\PostCategory;
use App\Services\Taxonomy\TaxonomyService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class TaxonomyController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function categories(): JsonResponse
    {
        return $this->successResponse(
            PostCategoryResource::collection($this->taxonomy->categories())->resolve(),
            'Categories fetched successfully.'
        );
    }

    public function subcategories(PostCategory $category): JsonResponse
    {
        return $this->successResponse(
            PostSubcategoryResource::collection($this->taxonomy->subcategories($category))->resolve(),
            'Subcategories fetched successfully.'
        );
    }
}
