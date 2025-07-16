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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vat')->nullable();
            $table->string('status')->nullable();
            // name stays—don't overwrite
            $table->string('address')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('city')->nullable();
            $table->boolean('protected')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->date('startdate')->nullable();
            $table->date('enddate')->nullable();
            $table->integer('employees')->nullable();
            $table->string('industrycode')->nullable();
            $table->string('industrydesc')->nullable();
            $table->string('companytype')->nullable();
            $table->string('companydesc')->nullable();
            $table->json('owners')->nullable();
            $table->json('financial_summary')->nullable();
            $table->json('error')->nullable();  // to store API errors / no-results
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'vat','status','address','zipcode','city','protected',
                'phone','website','email','fax','startdate','enddate',
                'employees','industrycode','industrydesc','companytype',
                'companydesc','owners','financial_summary','error'
            ]);
        });
    }
};
