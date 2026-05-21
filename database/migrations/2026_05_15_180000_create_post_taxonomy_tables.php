<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_subcategories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['post_category_id', 'slug']);
        });

        Schema::table('news_posts', function (Blueprint $table): void {
            $table->foreignId('post_category_id')->nullable()->after('body')->constrained('post_categories')->nullOnDelete();
            $table->foreignId('post_subcategory_id')->nullable()->after('post_category_id')->constrained('post_subcategories')->nullOnDelete();
            $table->index(['post_category_id', 'post_subcategory_id']);
        });

        $now = now();

        $categories = [
            ['name' => 'General', 'slug' => 'general', 'description' => 'Top stories and broad local coverage.', 'sort_order' => 10],
            ['name' => 'Community', 'slug' => 'community', 'description' => 'Community updates, events, and local stories.', 'sort_order' => 20],
            ['name' => 'Politics', 'slug' => 'politics', 'description' => 'Government, public policy, and elections.', 'sort_order' => 30],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'School, county, and regional sports coverage.', 'sort_order' => 40],
            ['name' => 'Weather', 'slug' => 'weather', 'description' => 'Forecasts, alerts, and emergency weather coverage.', 'sort_order' => 50],
            ['name' => 'Traffic', 'slug' => 'traffic', 'description' => 'Road conditions, closures, and transit updates.', 'sort_order' => 60],
        ];

        DB::table('post_categories')->insert(
            array_map(
                fn (array $category): array => $category + [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $categories,
            ),
        );

        $categoryIds = DB::table('post_categories')
            ->pluck('id', 'slug')
            ->all();

        $subcategories = [
            ['post_category_id' => $categoryIds['general'] ?? null, 'name' => 'Top Stories', 'slug' => 'top-stories', 'description' => 'High-priority general headlines.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['general'] ?? null, 'name' => 'County Updates', 'slug' => 'county-updates', 'description' => 'Routine county-wide news updates.', 'sort_order' => 20],
            ['post_category_id' => $categoryIds['community'] ?? null, 'name' => 'Local Updates', 'slug' => 'local-updates', 'description' => 'Hyper-local community news and notices.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['community'] ?? null, 'name' => 'Events', 'slug' => 'events', 'description' => 'Community events and gatherings.', 'sort_order' => 20],
            ['post_category_id' => $categoryIds['politics'] ?? null, 'name' => 'Local Government', 'slug' => 'local-government', 'description' => 'City, county, and regional government activity.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['politics'] ?? null, 'name' => 'Elections', 'slug' => 'elections', 'description' => 'Election coverage and campaign updates.', 'sort_order' => 20],
            ['post_category_id' => $categoryIds['sports'] ?? null, 'name' => 'School Sports', 'slug' => 'school-sports', 'description' => 'High school and school sports news.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['sports'] ?? null, 'name' => 'County Sports', 'slug' => 'county-sports', 'description' => 'Broader county sports coverage.', 'sort_order' => 20],
            ['post_category_id' => $categoryIds['weather'] ?? null, 'name' => 'Alerts', 'slug' => 'alerts', 'description' => 'Severe weather alerts and warnings.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['weather'] ?? null, 'name' => 'Forecasts', 'slug' => 'forecasts', 'description' => 'Routine weather forecasts.', 'sort_order' => 20],
            ['post_category_id' => $categoryIds['traffic'] ?? null, 'name' => 'Road Conditions', 'slug' => 'road-conditions', 'description' => 'Traffic advisories and road conditions.', 'sort_order' => 10],
            ['post_category_id' => $categoryIds['traffic'] ?? null, 'name' => 'Closures', 'slug' => 'closures', 'description' => 'Lane closures and detours.', 'sort_order' => 20],
        ];

        DB::table('post_subcategories')->insert(
            array_map(
                fn (array $subcategory): array => $subcategory + [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_values(array_filter(
                    $subcategories,
                    fn (array $subcategory): bool => filled($subcategory['post_category_id']),
                )),
            ),
        );

        $subcategoryIds = DB::table('post_subcategories')
            ->pluck('id', 'slug')
            ->all();

        $topicDefaults = [
            'general' => 'top-stories',
            'community' => 'local-updates',
            'politics' => 'local-government',
            'sports' => 'school-sports',
            'weather' => 'alerts',
            'traffic' => 'road-conditions',
        ];

        foreach ($topicDefaults as $topicSlug => $subcategorySlug) {
            $categoryId = $categoryIds[$topicSlug] ?? null;
            $subcategoryId = $subcategoryIds[$subcategorySlug] ?? null;

            if (! $categoryId) {
                continue;
            }

            DB::table('news_posts')
                ->where('topic', $topicSlug)
                ->update([
                    'post_category_id' => $categoryId,
                    'post_subcategory_id' => $subcategoryId,
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news_posts')) {
            $this->dropForeignKeyIfExists('news_posts', 'post_subcategory_id');
            $this->dropForeignKeyIfExists('news_posts', 'post_category_id');

            Schema::table('news_posts', function (Blueprint $table): void {
                if (Schema::hasColumn('news_posts', 'post_subcategory_id')) {
                    $table->dropColumn('post_subcategory_id');
                }

                if (Schema::hasColumn('news_posts', 'post_category_id')) {
                    $table->dropColumn('post_category_id');
                }
            });
        }

        Schema::dropIfExists('post_subcategories');
        Schema::dropIfExists('post_categories');
    }

    protected function dropForeignKeyIfExists(string $table, string $column): void
    {
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraint,
            ));
        }
    }
};
