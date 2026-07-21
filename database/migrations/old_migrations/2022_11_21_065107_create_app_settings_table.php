<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name', 100);
            $table->string('support_email', 100)->nullable()->default('support@almir.uz');
            $table->text('description')->nullable();
            $table->string('contact_phone', 20)->nullable()->default('+999 890 757 45 775');
            $table->string('contact_email', 100)->nullable()->default('contact@almir.uz');
            $table->string('contact_fax', 100)->nullable()->default('fax@almir.uz');
            $table->string('contact_address')->nullable()->default('fax@almir.uz');
            $table->string('app_version', 10)->nullable()->default('v2.0.1');
            $table->string('referent_cost_file')->nullable()->default('/upload/config/ref_cost.pdf');
            $table->string('reg_cost_glc')->nullable()->default('/upload/config/reg_gls.pdf');
            $table->string('customer_cost_file')->nullable()->default('/upload/config/customer_costs.pdf');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_settings');
    }
}
