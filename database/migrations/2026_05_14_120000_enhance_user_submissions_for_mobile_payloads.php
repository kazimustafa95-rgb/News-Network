<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_submissions', function (Blueprint $table) {
            $table->string('title')->nullable()->after('county_id');
            $table->string('location_label')->nullable()->after('title');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::table('user_submissions', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropColumn(['title', 'location_label']);
        });
    }
};
