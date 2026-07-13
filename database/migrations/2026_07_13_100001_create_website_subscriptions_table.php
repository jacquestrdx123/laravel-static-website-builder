<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('manual_editing');
            $table->string('status')->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_subscriptions');
    }
};
