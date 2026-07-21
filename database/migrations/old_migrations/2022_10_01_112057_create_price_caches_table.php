<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceCachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_caches', function (Blueprint $table) {
            $table->id();
            $table->string('date', 15);
            $table->decimal('price_usd', 13, 2)->nullable()->default(0.00);
            $table->decimal('price_uzs', 13, 2)->nullable()->default(0.00);
            $table->decimal('price_eur', 13, 2)->nullable()->default(0.00);
            $table->decimal('price_rub', 13, 2)->nullable()->default(0.00);
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
        Schema::dropIfExists('price_caches');
    }
}
