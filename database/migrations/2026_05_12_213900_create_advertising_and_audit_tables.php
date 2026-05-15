<?php

use App\Enums\AdvertisementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('media_type')->default('image')->index();
            $table->string('disk');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('destination_url');
            $table->string('status')->default(AdvertisementStatus::Draft->value)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedTinyInteger('priority')->default(0)->index();
            $table->unsignedTinyInteger('slot_interval')->default(5);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('advertisement_counties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_id')->constrained('advertisements')->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['advertisement_id', 'county_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action')->index();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('advertisement_counties');
        Schema::dropIfExists('advertisements');
    }
};
