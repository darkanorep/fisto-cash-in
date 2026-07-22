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
        Schema::create('account_title_entry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_title_id')->nullable();
            $table->unsignedBigInteger('entry_id')->nullable();
            $table->string('code')->nullable();
            $table->string('title')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_group')->nullable();
            $table->string('sub_group')->nullable();
            $table->string('financial_statement')->nullable();
            $table->string('normal_balance')->nullable();
            $table->string('unit')->nullable();
            $table->string('allocation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_title_entry');
    }
};
