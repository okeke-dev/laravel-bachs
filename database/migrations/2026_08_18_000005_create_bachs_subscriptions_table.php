<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bachs_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('bachs_id')->unique();
            $table->string('customer_id')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->string('status');
            $table->string('collection_method')->nullable();
            $table->string('currency')->nullable();
            $table->string('amount')->nullable();
            $table->integer('quantity')->default(1);
            $table->json('billing_cycle')->nullable();
            $table->string('current_period_start')->nullable();
            $table->string('current_period_end')->nullable();
            $table->string('next_billed_at')->nullable();
            $table->string('trial_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('canceled_at')->nullable();
            $table->string('product_id')->nullable();
            $table->json('items')->nullable();
            $table->json('metadata')->nullable();
            $table->string('bachs_created_at')->nullable();
            $table->string('bachs_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bachs_subscriptions');
    }
};
