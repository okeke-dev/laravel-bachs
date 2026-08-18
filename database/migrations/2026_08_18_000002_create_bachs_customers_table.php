<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bachs_customers', function (Blueprint $table) {
            $table->id();
            $table->string('bachs_id')->unique();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->json('metadata')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('bachs_created_at')->nullable();
            $table->string('bachs_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bachs_customers');
    }
};
