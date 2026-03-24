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
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('payment_date')->nullable()->change();
            $table->timestamp('check_date')->nullable()->change();
            $table->timestamp('deposit_date')->nullable()->change();
            $table->timestamp('date_cleared')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->change();
            $table->date('check_date')->nullable()->change();
            $table->date('deposit_date')->nullable()->change();
            $table->date('date_cleared')->nullable()->change();
        });
    }
};
