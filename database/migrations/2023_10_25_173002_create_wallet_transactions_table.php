<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('reference');
            $table->decimal('amount', 12, 2);
            $table->double('charges')->default(0.0);
            $table->string('prev_balance');
            $table->string('new_balance');
            $table->string('service_type');
            $table->string('transaction_type')->nullable();
            $table->string('status');
            $table->string('channel')->nullable();
            $table->boolean('is_commission')->default(false);
            $table->string('narration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
