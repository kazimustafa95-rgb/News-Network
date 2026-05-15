<?php

use App\Enums\NewsPostStatus;
use App\Enums\PostTopic;
use App\Enums\UserSubmissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('status')->default(UserSubmissionStatus::Pending->value)->index();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_post_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_submission_id')->nullable()->constrained('user_submissions')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('topic')->default(PostTopic::General->value)->index();
            $table->string('source_type')->default('admin_original')->index();
            $table->string('status')->default(NewsPostStatus::Draft->value)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_breaking')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archive_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['county_id', 'status', 'published_at']);
        });

        Schema::table('user_submissions', function (Blueprint $table) {
            $table->foreign('approved_post_id')->references('id')->on('news_posts')->nullOnDelete();
        });

        Schema::create('post_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->nullable()->constrained('news_posts')->cascadeOnDelete();
            $table->foreignId('user_submission_id')->nullable()->constrained('user_submissions')->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_primary')->default(true)->index();
            $table->string('processing_status')->default('pending')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained('news_posts')->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->date('archive_date')->index();
            $table->unsignedInteger('price_cents')->default(999);
            $table->char('currency', 3)->default('USD');
            $table->string('access_scope')->default('day_pass');
            $table->timestamps();
            $table->unique('news_post_id');
            $table->index(['county_id', 'archive_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_archives');
        Schema::dropIfExists('post_videos');
        Schema::table('user_submissions', function (Blueprint $table) {
            $table->dropForeign(['approved_post_id']);
        });
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('user_submissions');
    }
};
