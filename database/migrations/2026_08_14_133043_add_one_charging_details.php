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
            $table->string('charge_code')->nullable()->after('charge_id');
            $table->string('company_code')->nullable()->after('charge_code');
            $table->string('company_name')->nullable()->after('company_code');
            $table->string('business_unit_code')->nullable()->after('company_name');
            $table->string('business_unit_name')->nullable()->after('business_unit_code');
            $table->string('department_code')->nullable()->after('business_unit_name');
            $table->string('department_name')->nullable()->after('department_code');
            $table->string('unit_code')->nullable()->after('department_name');
            $table->string('unit_name')->nullable()->after('unit_code');
            $table->string('sub_unit_code')->nullable()->after('unit_name');
            $table->string('sub_unit_name')->nullable()->after('sub_unit_code');
            $table->string('location_code')->nullable()->after('sub_unit_name');
            $table->string('location_name')->nullable()->after('location_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('charge_code');
            $table->dropColumn('company_code');
            $table->dropColumn('company_name');
            $table->dropColumn('business_unit_code');
            $table->dropColumn('business_unit_name');
            $table->dropColumn('department_code');
            $table->dropColumn('department_name');
            $table->dropColumn('unit_code');
            $table->dropColumn('unit_name');
            $table->dropColumn('sub_unit_code');
            $table->dropColumn('sub_unit_name');
            $table->dropColumn('location_code');
            $table->dropColumn('location_name');
        });
    }
};
