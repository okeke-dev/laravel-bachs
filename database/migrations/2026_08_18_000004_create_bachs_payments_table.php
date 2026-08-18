<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bachs_payments', function (Blueprint $table) {
            $table->id();
            $table->string('bachs_id')->unique();
            $table->string('charge_id')->nullable()->unique();
            $table->string('reference')->nullable();
            $table->string('billing_reason')->nullable();
            $table->string('checkout_id')->nullable();
            $table->string('status');
            $table->string('amount')->nullable();
            $table->string('amount_paid')->nullable();
            $table->string('amount_remaining')->nullable();
            $table->string('currency')->nullable();
            $table->string('fee_usd')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('subscription_id')->nullable();
            $table->json('line_items')->nullable();
            $table->json('refunds')->nullable();
            $table->json('status_history')->nullable();
            $table->json('metadata')->nullable();
            $table->string('bachs_created_at')->nullable();
            $table->string('bachs_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bachs_payments');
    }
};
