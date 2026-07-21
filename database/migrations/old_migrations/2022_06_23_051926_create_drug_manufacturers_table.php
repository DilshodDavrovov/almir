<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugManufacturersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drug_manufacturers', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('manufacturer_id')->constrained('manufacturers')->onDelete('cascade')->index("manufacturer_id");
            $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade')->index("drug_id");
            $table->integer('counter')->unsigned()->unique()->index("counter");
            $table->boolean('is_active')->nullable()->default(false);
            $table->boolean('deleted')->nullable()->default(false);
            $table->timestamps();

            //SETTING THE PRIMARY KEYS
            $table->primary(['user_id','manufacturer_id', 'drug_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drug_manufacturers');
    }
}
