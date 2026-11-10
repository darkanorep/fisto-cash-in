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
        Schema::table('banks', function (Blueprint $table) {
            $table->foreignId('tagging_account_title_id')->nullable()->after('branch')->constrained('account_titles');
            $table->foreignId('deposit_account_title_id')->nullable()->after('tagging_account_title_id')->constrained('account_titles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropForeign(['tagging_account_title_id']);
            $table->dropForeign(['deposit_account_title_id']);
            $table->dropColumn(['tagging_account_title_id', 'deposit_account_title_id']);
        });
    }
};
