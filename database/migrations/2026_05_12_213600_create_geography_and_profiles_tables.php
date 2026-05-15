<?php

use App\Enums\CountyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 12)->nullable();
            $table->string('type')->default('state');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['country_id', 'name']);
        });

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default(CountyStatus::ComingSoon->value)->index();
            $table->date('launch_date')->nullable()->index();
            $table->string('timezone')->default('America/Chicago');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['region_id', 'slug']);
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('avatar_path')->nullable();
            $table->foreignId('default_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('default_region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('default_county_id')->nullable()->constrained('counties')->nullOnDelete();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->string('source')->default('manual');
            $table->timestamps();
            $table->unique(['user_id', 'county_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_locations');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }
};
