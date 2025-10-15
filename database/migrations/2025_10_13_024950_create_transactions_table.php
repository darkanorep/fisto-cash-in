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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type')->nullable();
            $table->string('category')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->string('customer_name')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained();
            $table->string('bank_name')->nullable();
            $table->string('check_no')->nullable();
            $table->date('check_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2)->default(0);
            $table->foreignId('charge_id')->nullable()->constrained();
            $table->string('charge_name')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->string('reason')->nullable();
            $table->boolean('is_tagged')->default(false);
            $table->boolean('is_cleared')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
