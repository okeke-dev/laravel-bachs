<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bachs_products', function (Blueprint $table) {
            $table->id();
            $table->string('bachs_id')->unique();
            $table->string('organization_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->json('billing_cycle')->nullable();
            $table->json('trial_period')->nullable();
            $table->string('bachs_created_at')->nullable();
            $table->string('bachs_updated_at')->nullable();
            $table->string('bachs_archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bachs_products');
    }
};
