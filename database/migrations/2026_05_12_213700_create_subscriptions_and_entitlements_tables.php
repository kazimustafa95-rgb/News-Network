<?php

use App\Enums\ArchivePurchaseStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_product_id');
            $table->string('provider_transaction_id')->unique();
            $table->string('plan_code');
            $table->string('status')->default(SubscriptionStatus::Pending->value)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('receipt_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('archive_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->date('archive_date')->index();
            $table->string('provider');
            $table->string('provider_transaction_id')->unique();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('status')->default(ArchivePurchaseStatus::Pending->value)->index();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'county_id', 'archive_date'], 'archive_purchases_user_county_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_purchases');
        Schema::dropIfExists('subscriptions');
    }
};
