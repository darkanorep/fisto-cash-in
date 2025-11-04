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
            $table->date('deposit_date')->nullable()->after('status');
            $table->string('bank_deposit')->nullable()->after('deposit_date');
            $table->text('deposit_remarks')->nullable()->after('bank_deposit');
            $table->string('tag_number')->nullable()->after('deposit_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
