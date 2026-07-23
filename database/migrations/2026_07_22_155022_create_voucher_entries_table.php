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
        Schema::create('voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable()->index();
            $table->foreignId('transaction_id')
                ->constrained('transactions');
            $table->string('code')->nullable();
            $table->string('title')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_group')->nullable();
            $table->string('sub_group')->nullable();
            $table->string('financial_statement')->nullable();
            $table->string('normal_balance')->nullable();
            $table->string('unit')->nullable();
            $table->string('allocation')->nullable();
            $table->string('charge_name')->nullable();
            $table->string('company_code')->nullable();
            $table->string('company_name')->nullable();
            $table->string('business_unit_code')->nullable();
            $table->string('business_unit_name')->nullable();
            $table->string('department_code')->nullable();
            $table->string('department_name')->nullable();
            $table->string('unit_code')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('sub_unit_code')->nullable();
            $table->string('sub_unit_name')->nullable();
            $table->string('location_code')->nullable();
            $table->string('location_name')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_entries');
    }
};
