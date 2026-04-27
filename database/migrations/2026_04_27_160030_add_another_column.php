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
        Schema::table('account_titles', function (Blueprint $table) {
            $table->bigInteger('sync_id')->nullable()->after('id');
            $table->string('allocation')->nullable()->after('financial_statement');
            $table->string('charge')->nullable()->after('financial_statement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_titles', function (Blueprint $table) {
            //
        });
    }
};
