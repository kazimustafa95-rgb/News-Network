<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('device_name')->nullable();
            $table->string('otp_code');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('last_sent_at')->nullable()->index();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
